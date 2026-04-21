<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Service\ProductSyncService;
use App\SharedBundle\Message\ProductSyncedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProductSyncedMessageHandler
{
    public function __construct(
        private readonly ProductSyncService $productSyncService,
    ) {
    }

    public function __invoke(ProductSyncedMessage $message): void
    {
        $this->productSyncService->sync($message);
    }
}
