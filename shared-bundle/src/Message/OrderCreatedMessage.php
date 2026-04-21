<?php

declare(strict_types=1);

namespace App\SharedBundle\Message;

/**
 * Message payload for notifying product-service about a created order.
 *
 * Field shape:
 * - orderId (UUID)
 * - productId (UUID)
 * - quantityOrdered
 */
final readonly class OrderCreatedMessage
{
    public function __construct(
        public string $orderId,
        public string $productId,
        public int $quantityOrdered,
    ) {
    }
}
