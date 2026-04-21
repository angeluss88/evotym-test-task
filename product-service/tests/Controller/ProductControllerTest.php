<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Tests\Support\DatabaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProductControllerTest extends DatabaseWebTestCase
{
    public function testCreateProductSuccessfully(): void
    {
        $this->postJson('/products', [
            'name' => 'Coffee Mug',
            'price' => 12.99,
            'quantity' => 100,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = $this->decodeJsonResponse();

        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $response['id']);
        self::assertSame('Coffee Mug', $response['name']);
        self::assertSame(12.99, $response['price']);
        self::assertSame(100, $response['quantity']);
        self::assertSame(1, $this->entityManager->getRepository(Product::class)->count([]));
    }

    public function testCreateProductReturnsValidationErrorsForInvalidInput(): void
    {
        $this->postJson('/products', [
            'name' => '',
            'price' => -1,
            'quantity' => -3,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->decodeJsonResponse();

        self::assertSame('Validation failed.', $response['message']);
        self::assertArrayHasKey('name', $response['errors']);
        self::assertArrayHasKey('price', $response['errors']);
        self::assertArrayHasKey('quantity', $response['errors']);
        self::assertSame(0, $this->entityManager->getRepository(Product::class)->count([]));
    }
}
