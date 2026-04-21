<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\SharedBundle\Message\ProductSyncedMessage;
use Doctrine\ORM\EntityManagerInterface;

final class ProductSyncService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function sync(ProductSyncedMessage $message): void
    {
        $product = $this->productRepository->find($message->id);

        if (!$product instanceof Product) {
            $product = new Product(
                $message->id,
                $message->name,
                $message->price,
                $message->quantity,
            );

            $this->entityManager->persist($product);
        } else {
            $product
                ->setName($message->name)
                ->setPrice($message->price)
                ->setQuantity($message->quantity);
        }

        $this->entityManager->flush();
    }
}
