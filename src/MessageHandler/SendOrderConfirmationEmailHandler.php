<?php

namespace App\MessageHandler;

use App\Classe\Mail;
use App\Message\SendOrderConfirmationEmail;
use App\Repository\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendOrderConfirmationEmailHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private Mail $mail
    ) {}

    public function __invoke(SendOrderConfirmationEmail $message): void
    {
        //1. Fetch the order from database
        $order = $this->orderRepository->find($message->getOrderId());

        if (!$order) {
            return; //order not found, skip
        }
        //2. Get customer info
        $user = $order->getUser();

        //3.uild product list for email
        $productList='';
        foreach ($order->getOrderDetails() as $detail) {
            $productList .= sprintf(
                "- %s (x%d): %.2f €<br/>",
                $detail->getProductName(),
                $detail->getProductQuantity(),
                $detail->getProductPriceWt() * $detail->getProductQuantity()
            );
        }
        //4. Send an email
        $this->mail->send(
            $user->getEmail(),
            $user->getFirstname(). ' ' . $user->getLastname(),
            'Confirmation de votre commande #' . $order->getId(),
            'order_confirmation.html',
            [
                'firstname' => $user->getFirstname(),
                'order_id' => $order->getId(),
                'order_date' => $order->getCreatedAt()->format('d/m/Y H:i'),
                'products' =>$productList,
                'total' => number_format($order->getTotalWt(), 2, ',', ' ')
            ]
        );
    }
}