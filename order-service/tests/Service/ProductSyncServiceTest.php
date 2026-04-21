<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ProductSyncService;
use App\SharedBundle\Message\ProductSyncedMessage;
use App\Tests\Support\DatabaseKernelTestCase;

final class ProductSyncServiceTest extends DatabaseKernelTestCase
{
    private ProductSyncService $productSyncService;
    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productSyncService = static::getContainer()->get(ProductSyncService::class);
        $this->productRepository = static::getContainer()->get(ProductRepository::class);
    }

    public function testHandlingProductSyncedMessageCreatesProductWhenMissing(): void
    {
        $this->productSyncService->sync(new ProductSyncedMessage(
            '019db0a1-3fc8-768e-85aa-1786a5c95c38',
            'Tea Cup',
            '9.50',
            25,
        ));

        /** @var Product $product */
        $product = $this->productRepository->find('019db0a1-3fc8-768e-85aa-1786a5c95c38');

        self::assertInstanceOf(Product::class, $product);
        self::assertSame('Tea Cup', $product->getName());
        self::assertSame('9.50', $product->getPrice());
        self::assertSame(25, $product->getQuantity());
    }

    public function testHandlingProductSyncedMessageUpdatesExistingProductInsteadOfDuplicatingIt(): void
    {
        $existingProduct = new Product(
            '019db0a1-3fc8-768e-85aa-1786a5c95c38',
            'Old Name',
            '1.00',
            1,
        );

        $this->entityManager->persist($existingProduct);
        $this->entityManager->flush();

        $this->productSyncService->sync(new ProductSyncedMessage(
            '019db0a1-3fc8-768e-85aa-1786a5c95c38',
            'Tea Cup',
            '9.50',
            25,
        ));

        /** @var Product $product */
        $product = $this->productRepository->find('019db0a1-3fc8-768e-85aa-1786a5c95c38');

        self::assertSame(1, $this->productRepository->count([]));
        self::assertSame('Tea Cup', $product->getName());
        self::assertSame('9.50', $product->getPrice());
        self::assertSame(25, $product->getQuantity());
    }
}
