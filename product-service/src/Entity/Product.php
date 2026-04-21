<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use App\SharedBundle\Entity\AbstractProduct;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product extends AbstractProduct
{
    public function __construct(
        string $id,
        string $name,
        string $price,
        int $quantity,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
    }
}
