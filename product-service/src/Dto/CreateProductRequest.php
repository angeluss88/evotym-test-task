<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateProductRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(max: 255)]
        public mixed $name,
        #[Assert\NotNull]
        #[Assert\Type('numeric')]
        #[Assert\GreaterThanOrEqual(0)]
        public mixed $price,
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\GreaterThanOrEqual(0)]
        public mixed $quantity,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            $payload['name'] ?? null,
            $payload['price'] ?? null,
            $payload['quantity'] ?? null,
        );
    }
}
