<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Uuid]
        public mixed $productId,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(max: 255)]
        public mixed $customerName,
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\GreaterThan(0)]
        public mixed $quantityOrdered,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            $payload['productId'] ?? null,
            $payload['customerName'] ?? null,
            $payload['quantityOrdered'] ?? null,
        );
    }
}
