<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateProductRequest;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Exception\ValidationException;
use App\Repository\ProductRepository;
use App\SharedBundle\Dto\ProductDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function create(CreateProductRequest $request): ProductDto
    {
        $this->assertValid($request);

        $product = new Product(
            Uuid::v7()->toRfc4122(),
            trim((string) $request->name),
            $this->normalizePrice($request->price),
            (int) $request->quantity,
        );

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->toDto($product);
    }

    /**
     * @return list<ProductDto>
     */
    public function list(): array
    {
        return array_map($this->toDto(...), $this->productRepository->findAllOrderedByName());
    }

    public function get(string $id): ProductDto
    {
        $product = $this->productRepository->find($id);

        if (!$product instanceof Product) {
            throw ProductNotFoundException::forId($id);
        }

        return $this->toDto($product);
    }

    private function assertValid(CreateProductRequest $request): void
    {
        $violations = $this->validator->validate($request);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }
    }

    private function normalizePrice(mixed $price): string
    {
        return number_format((float) $price, 2, '.', '');
    }

    private function toDto(Product $product): ProductDto
    {
        return new ProductDto(
            $product->getId(),
            $product->getName(),
            $product->getPrice(),
            $product->getQuantity(),
        );
    }
}
