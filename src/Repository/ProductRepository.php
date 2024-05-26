<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByFiltersQueryBuilder($category, $sort)
    {
        $qb = $this->createQueryBuilder('p');

        if ($category) {
            $qb->andWhere('p.category = :category')
            ->setParameter('category', $category);
        }

        $qb->orderBy('p.title', $sort);

        return $qb;
    }
}
