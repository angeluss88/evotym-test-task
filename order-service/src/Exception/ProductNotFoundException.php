<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class ProductNotFoundException extends RuntimeException
{
    public static function forId(string $id): self
    {
        return new self(sprintf('Product "%s" was not found.', $id));
    }
}
