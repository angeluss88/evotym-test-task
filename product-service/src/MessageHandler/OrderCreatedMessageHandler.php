<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Service\ProductInventoryService;
use App\SharedBundle\Message\OrderCreatedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class OrderCreatedMessageHandler
{
    public function __construct(
        private readonly ProductInventoryService $productInventoryService,
    ) {
    }

    public function __invoke(OrderCreatedMessage $message): void
    {
        $this->productInventoryService->handleOrderCreated($message);
    }
}
