<?php

namespace App\Message;

/**
 * Message dispatched when an order is successfully paid.
 *
 * This message is handled asynchronously by SendOrderConfirmationEmailHandler.
 * It only contains the order ID - the handler fetches data from the database.
 */
class SendOrderConfirmationEmail
{
    public function __construct(
        // Stored the order ID (using PHP 8 constructor property promotion)
        private int $orderId
    )
    {}
    // Returns the order ID so the handler can use it.
    public function getOrderId(): int
    {
        return $this->orderId;
    }
}