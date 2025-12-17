<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Repository\AddressRepository;
use App\Repository\CarrierRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class OrderController extends AbstractController
{
    #[Route('/orders', name: 'api_orders_create', methods: ['POST'])]
    public function createOrder(
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
        if (!isset($data['addressId'], $data['carrierId'], $data['items'])) {
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

        // Format delivery address as string
        $deliveryAddress = $address->getFirstname() . ' ' . $address->getLastname() . '<br/>';
        $deliveryAddress .= $address->getAddress() . '<br/>';
        $deliveryAddress .= $address->getPostal() . ' ' . $address->getCity() . '<br/>';
        $deliveryAddress .= $address->getCountry() . '<br/>';
        $deliveryAddress .= 'Tél: ' . $address->getPhone();

        $order->setDelivery($deliveryAddress);

        $em->persist($order);

        // Create order details
        foreach ($data['items'] as $item) {
            $product = $productRepo->find($item['productId']);
            if (!$product) {
                continue;
            }

            $orderDetail = new OrderDetail();
            $orderDetail->setProductName($product->getName());
            $orderDetail->setProductIllustration($product->getIllustration());
            $orderDetail->setProductPrice($product->getPrice()); // Price HT
            $orderDetail->setProductQuantity($item['quantity']);
            $orderDetail->setProductTva($product->getTva());
            $orderDetail->setMyOrder($order);

            $em->persist($orderDetail);
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'orderId' => $order->getId(),
            'total' => $order->getTotalWt(),
            'reference' => 'CMD-' . $order->getId() . '-' . date('Ymd')
        ], Response::HTTP_CREATED);
    }

    #[Route('/orders', name: 'api_orders_list', methods: ['GET'])]
    public function getOrders(OrderRepository $orderRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $orders = $orderRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $ordersData = [];
        foreach ($orders as $order) {
            $orderDetails = [];
            foreach ($order->getOrderDetails() as $detail) {
                $orderDetails[] = [
                    'productName' => $detail->getProductName(),
                    'productIllustration' => $detail->getProductIllustration(),
                    'productPrice' => $detail->getProductPriceWt(), // Price TTC
                    'productQuantity' => $detail->getProductQuantity(),
                ];
            }

            $ordersData[] = [
                'id' => $order->getId(),
                'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                'state' => $order->getState(),
                'carrierName' => $order->getCarrierName(),
                'carrierPrice' => $order->getCarrierPrice(),
                'delivery' => $order->getDelivery(),
                'orderDetails' => $orderDetails,
                'total' => $order->getTotalWt()
            ];
        }

        return new JsonResponse($ordersData);
    }

    #[Route('/orders/{id}', name: 'api_orders_get', methods: ['GET'])]
    public function getOrder(int $id, OrderRepository $orderRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $order = $orderRepo->find($id);

        if (!$order || $order->getUser() !== $user) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $orderDetails = [];
        foreach ($order->getOrderDetails() as $detail) {
            $orderDetails[] = [
                'productName' => $detail->getProductName(),
                'productIllustration' => $detail->getProductIllustration(),
                'productPrice' => $detail->getProductPriceWt(),
                'productQuantity' => $detail->getProductQuantity(),
            ];
        }

        return new JsonResponse([
            'id' => $order->getId(),
            'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            'state' => $order->getState(),
            'carrierName' => $order->getCarrierName(),
            'carrierPrice' => $order->getCarrierPrice(),
            'delivery' => $order->getDelivery(),
            'orderDetails' => $orderDetails,
            'total' => $order->getTotalWt()
        ]);
    }
}