<?php

declare(strict_types=1);

namespace App\SharedBundle\Dto;

/**
 * Shared product read model used across services.
 *
 * Field shape:
 * - id (UUID)
 * - name
 * - price
 * - quantity
 */
final readonly class ProductDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $price,
        public int $quantity,
    ) {
    }
}
