<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateOrderRequest;
use App\Entity\Order;
use App\Entity\Product;
use App\Service\OrderService;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders', name: 'order_')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
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

        $order = $this->orderService->create(CreateOrderRequest::fromArray($payload));

        return $this->json($this->normalizeOrder($order), Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $orders = array_map($this->normalizeOrder(...), $this->orderService->list());

        return $this->json(['data' => $orders]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->normalizeOrder($this->orderService->get($id)));
    }

    /**
     * @return array{
     *     orderId: string,
     *     product: array{id: string, name: string, price: float, quantity: int},
     *     customerName: string,
     *     quantityOrdered: int,
     *     orderStatus: string
     * }
     */
    private function normalizeOrder(Order $order): array
    {
        return [
            'orderId' => $order->getId(),
            'product' => $this->normalizeProduct($order->getProduct()),
            'customerName' => $order->getCustomerName(),
            'quantityOrdered' => $order->getQuantityOrdered(),
            'orderStatus' => $order->getOrderStatus(),
        ];
    }

    /**
     * @return array{id: string, name: string, price: float, quantity: int}
     */
    private function normalizeProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => (float) $product->getPrice(),
            'quantity' => $product->getQuantity(),
        ];
    }
}
