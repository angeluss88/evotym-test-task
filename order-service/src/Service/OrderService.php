<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateOrderRequest;
use App\Entity\Order;
use App\Entity\Product;
use App\Exception\InsufficientProductQuantityException;
use App\Exception\OrderNotFoundException;
use App\Exception\ProductNotFoundException;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\SharedBundle\Message\OrderCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function create(CreateOrderRequest $request): Order
    {
        $this->assertValid($request);

        $product = $this->productRepository->find((string) $request->productId);

        if (!$product instanceof Product) {
            throw ProductNotFoundException::forId((string) $request->productId);
        }

        $quantityOrdered = (int) $request->quantityOrdered;
        $availableQuantity = $product->getQuantity();

        if ($availableQuantity < $quantityOrdered) {
            throw InsufficientProductQuantityException::forProduct(
                $product->getId(),
                $quantityOrdered,
                $availableQuantity,
            );
        }

        $order = new Order(
            Uuid::v7()->toRfc4122(),
            $product,
            trim((string) $request->customerName),
            $quantityOrdered,
        );

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new OrderCreatedMessage(
            $order->getId(),
            $product->getId(),
            $quantityOrdered,
        ));

        return $order;
    }

    /**
     * @return list<Order>
     */
    public function list(): array
    {
        return $this->orderRepository->findAllOrderedByNewestFirst();
    }

    public function get(string $id): Order
    {
        $order = $this->orderRepository->find($id);

        if (!$order instanceof Order) {
            throw OrderNotFoundException::forId($id);
        }

        return $order;
    }

    private function assertValid(CreateOrderRequest $request): void
    {
        $violations = $this->validator->validate($request);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }
    }
}
