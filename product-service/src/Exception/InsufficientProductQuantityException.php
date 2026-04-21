<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class InsufficientProductQuantityException extends RuntimeException
{
    public static function forProduct(string $productId, int $requested, int $available): self
    {
        return new self(sprintf(
            'Insufficient quantity for product "%s". Requested %d, available %d.',
            $productId,
            $requested,
            $available,
        ));
    }
}
