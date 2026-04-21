<?php

declare(strict_types=1);

namespace App\SharedBundle\Message;

/**
 * Message payload for product synchronization between services.
 *
 * Field shape:
 * - id (UUID)
 * - name
 * - price
 * - quantity
 */
final readonly class ProductSyncedMessage
{
    public function __construct(
        public string $id,
        public string $name,
        public string $price,
        public int $quantity,
    ) {
    }
}
