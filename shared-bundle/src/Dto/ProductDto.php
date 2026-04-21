<?php

declare(strict_types=1);

namespace App\SharedBundle\Dto;

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
