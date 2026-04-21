<?php

declare(strict_types=1);

namespace App\SharedBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    protected string $id;

    #[ORM\Column(length: 255)]
    protected string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    protected string $price;

    #[ORM\Column(type: 'integer')]
    protected int $quantity;

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
