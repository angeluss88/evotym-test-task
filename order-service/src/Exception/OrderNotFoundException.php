<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class OrderNotFoundException extends RuntimeException
{
    public static function forId(string $id): self
    {
        return new self(sprintf('Order "%s" was not found.', $id));
    }
}
