<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    public const STATUS_PROCESSING = 'Processing';

    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Product $product;

    #[ORM\Column(length: 255)]
    private string $customerName;

    #[ORM\Column(type: 'integer')]
    private int $quantityOrdered;

    #[ORM\Column(length: 50)]
    private string $orderStatus;

    public function __construct(
        string $id,
        Product $product,
        string $customerName,
        int $quantityOrdered,
        string $orderStatus = self::STATUS_PROCESSING,
    ) {
        $this->id = $id;
        $this->product = $product;
        $this->customerName = $customerName;
        $this->quantityOrdered = $quantityOrdered;
        $this->orderStatus = $orderStatus;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getQuantityOrdered(): int
    {
        return $this->quantityOrdered;
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }
}
