<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateProductRequest;
use App\Service\ProductService;
use App\SharedBundle\Dto\ProductDto;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', name: 'product_')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (JsonException $exception) {
            throw new BadRequestHttpException('Invalid JSON payload.', $exception);
        }

        $product = $this->productService->create(CreateProductRequest::fromArray($payload));

        return $this->json($this->normalizeProduct($product), Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $products = array_map($this->normalizeProduct(...), $this->productService->list());

        return $this->json(['data' => $products]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->normalizeProduct($this->productService->get($id)));
    }

    /**
     * Product API response shape:
     * - id (UUID)
     * - name
     * - price
     * - quantity
     *
     * @return array{id: string, name: string, price: float, quantity: int}
     */
    private function normalizeProduct(ProductDto $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'quantity' => $product->quantity,
        ];
    }
}
