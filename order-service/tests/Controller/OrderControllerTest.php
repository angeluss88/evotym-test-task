<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Tests\Support\DatabaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class OrderControllerTest extends DatabaseWebTestCase
{
    public function testCreateOrderSuccessfullyWhenProductExistsAndQuantityIsSufficient(): void
    {
        $product = $this->seedProduct(quantity: 10);

        $this->postJson('/orders', [
            'productId' => $product->getId(),
            'customerName' => 'John Doe',
            'quantityOrdered' => 3,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = $this->decodeJsonResponse();

        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $response['orderId']);
        self::assertSame('John Doe', $response['customerName']);
        self::assertSame(3, $response['quantityOrdered']);
        self::assertSame('Processing', $response['orderStatus']);
        self::assertSame($product->getId(), $response['product']['id']);
        self::assertSame(10, $response['product']['quantity']);

        /** @var Product $reloadedProduct */
        $reloadedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());

        self::assertSame(10, $reloadedProduct->getQuantity());
        self::assertSame(1, $this->entityManager->getRepository(Order::class)->count([]));
    }

    public function testCreateOrderFailsWhenProductDoesNotExist(): void
    {
        $this->postJson('/orders', [
            'productId' => '00000000-0000-4000-8000-000000000001',
            'customerName' => 'John Doe',
            'quantityOrdered' => 1,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $response = $this->decodeJsonResponse();

        self::assertStringContainsString('was not found', $response['message']);
        self::assertSame(0, $this->entityManager->getRepository(Order::class)->count([]));
    }

    public function testCreateOrderFailsWhenQuantityIsInsufficient(): void
    {
        $product = $this->seedProduct(quantity: 2);

        $this->postJson('/orders', [
            'productId' => $product->getId(),
            'customerName' => 'John Doe',
            'quantityOrdered' => 3,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->decodeJsonResponse();

        self::assertStringContainsString('Insufficient quantity', $response['message']);

        /** @var Product $reloadedProduct */
        $reloadedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());

        self::assertSame(2, $reloadedProduct->getQuantity());
        self::assertSame(0, $this->entityManager->getRepository(Order::class)->count([]));
    }

    public function testCreateOrderFailsWhenQuantityOrderedIsNotPositive(): void
    {
        $product = $this->seedProduct(quantity: 10);

        $this->postJson('/orders', [
            'productId' => $product->getId(),
            'customerName' => 'John Doe',
            'quantityOrdered' => 0,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->decodeJsonResponse();

        self::assertSame('Validation failed.', $response['message']);
        self::assertArrayHasKey('quantityOrdered', $response['errors']);
        self::assertSame(0, $this->entityManager->getRepository(Order::class)->count([]));
    }

    private function seedProduct(int $quantity): Product
    {
        $product = new Product(
            '019db0a1-3fc8-768e-85aa-1786a5c95c38',
            'Coffee Mug',
            '12.99',
            $quantity,
        );

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}
