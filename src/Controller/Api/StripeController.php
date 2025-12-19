<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Repository\AddressRepository;
use App\Repository\CarrierRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class StripeController extends AbstractController
{
    /**
     * Create order AND Stripe checkout session in one call
     * This replaces the previous /api/orders POST endpoint for payment flow
     */
    #[Route('/checkout/create-session', name: 'api_checkout_create_session', methods: ['POST'])]
    public function createCheckoutSession(
        Request $request,
        EntityManagerInterface $em,
        AddressRepository $addressRepo,
        CarrierRepository $carrierRepo,
        ProductRepository $productRepo
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (!isset($data['addressId'], $data['carrierId'], $data['items']) || empty($data['items'])) {
            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        // Get address and carrier
        $address = $addressRepo->find($data['addressId']);
        $carrier = $carrierRepo->find($data['carrierId']);

        if (!$address || $address->getUser() !== $user) {
            return new JsonResponse(['error' => 'Invalid address'], Response::HTTP_BAD_REQUEST);
        }

        if (!$carrier) {
            return new JsonResponse(['error' => 'Invalid carrier'], Response::HTTP_BAD_REQUEST);
        }

        // Create order
        $order = new Order();
        $order->setUser($user);
        $order->setCreatedAt(new \DateTime());
        $order->setCarrierName($carrier->getName());
        $order->setCarrierPrice($carrier->getPrice());
        $order->setState(1); // En attente de paiement

        // Format delivery address
        $deliveryAddress = $address->getFirstname() . ' ' . $address->getLastname() . '<br/>';
        $deliveryAddress .= $address->getAddress() . '<br/>';
        $deliveryAddress .= $address->getPostal() . ' ' . $address->getCity() . '<br/>';
        $deliveryAddress .= $address->getCountry() . '<br/>';
        $deliveryAddress .= 'Tél: ' . $address->getPhone();

        $order->setDelivery($deliveryAddress);

        $em->persist($order);

        // Create order details and prepare Stripe line items
        $stripeLineItems = [];

        foreach ($data['items'] as $item) {
            $product = $productRepo->find($item['productId']);
            if (!$product) {
                continue;
            }

            // Create order detail
            $orderDetail = new OrderDetail();
            $orderDetail->setProductName($product->getName());
            $orderDetail->setProductIllustration($product->getIllustration());
            $orderDetail->setProductPrice($product->getPrice());
            $orderDetail->setProductQuantity($item['quantity']);
            $orderDetail->setProductTva($product->getTva());
            $orderDetail->setMyOrder($order);

            $em->persist($orderDetail);

            // Add to Stripe line items
            $priceWithTax = $product->getPrice() * (1 + $product->getTva() / 100);

            $stripeLineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) round($priceWithTax * 100), // Stripe expects cents
                    'product_data' => [
                        'name' => $product->getName(),
                        'images' => [
                            $_ENV['DOMAIN'] . '/uploads/' . $product->getIllustration()
                        ]
                    ]
                ],
                'quantity' => $item['quantity'],
            ];
        }

        // Add shipping as a line item
        $stripeLineItems[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => (int) round($carrier->getPrice() * 100),
                'product_data' => [
                    'name' => 'Livraison : ' . $carrier->getName(),
                ]
            ],
            'quantity' => 1,
        ];

        // Flush to get order ID
        $em->flush();

        // Create Stripe Checkout Session
        try {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            $frontendDomain = $_ENV['FRONTEND_DOMAIN'] ?? 'http://localhost:3000';

            $checkoutSession = StripeSession::create([
                'customer_email' => $user->getEmail(),
                'line_items' => $stripeLineItems,
                'mode' => 'payment',
                'success_url' => $frontendDomain . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $frontendDomain . '/checkout/cancel',
                'metadata' => [
                    'order_id' => $order->getId(),
                ],
            ]);

            // Save Stripe session ID to order
            $order->setStripeSessionId($checkoutSession->id);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'checkoutUrl' => $checkoutSession->url,
                'sessionId' => $checkoutSession->id,
                'orderId' => $order->getId(),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Stripe error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify payment status after Stripe redirect
     */
    #[Route('/checkout/verify/{sessionId}', name: 'api_checkout_verify', methods: ['GET'])]
    public function verifyPayment(
        string $sessionId,
        OrderRepository $orderRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Find order by Stripe session ID
        $order = $orderRepo->findOneBy([
            'stripe_session_id' => $sessionId,
            'user' => $user
        ]);

        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        // Check Stripe session status
        try {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                // Update order state if not already done
                if ($order->getState() === 1) {
                    $order->setState(2); // Paiement validé
                    $em->flush();
                }

                return new JsonResponse([
                    'success' => true,
                    'paid' => true,
                    'orderId' => $order->getId(),
                    'orderState' => $order->getState(),
                ]);
            }

            return new JsonResponse([
                'success' => true,
                'paid' => false,
                'status' => $session->payment_status,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Verification failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

