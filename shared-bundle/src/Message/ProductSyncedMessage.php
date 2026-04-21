<?php

declare(strict_types=1);

namespace App\SharedBundle\Message;

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
