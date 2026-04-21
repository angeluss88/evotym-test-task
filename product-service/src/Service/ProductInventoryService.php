<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Exception\InsufficientProductQuantityException;
use App\Exception\ProductNotFoundException;
use App\Repository\ProductRepository;
use App\SharedBundle\Message\OrderCreatedMessage;
use App\SharedBundle\Message\ProductSyncedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ProductInventoryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function handleOrderCreated(OrderCreatedMessage $message): void
    {
        $product = $this->productRepository->find($message->productId);

        if (!$product instanceof Product) {
            throw ProductNotFoundException::forId($message->productId);
        }

        $availableQuantity = $product->getQuantity();

        if ($availableQuantity < $message->quantityOrdered) {
            throw InsufficientProductQuantityException::forProduct(
                $product->getId(),
                $message->quantityOrdered,
                $availableQuantity,
            );
        }

        $product->setQuantity($availableQuantity - $message->quantityOrdered);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new ProductSyncedMessage(
            $product->getId(),
            $product->getName(),
            $product->getPrice(),
            $product->getQuantity(),
        ));
    }
}
