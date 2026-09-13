<?php

namespace App\Repository;

use App\Entity\Category;
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

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function countLowStockProducts(int $threshold = 5): int
    {
        return (int) $this
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.quantity <= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLowStockProducts(int $threshold = 5): array
    {
        return $this
            ->createQueryBuilder('p')
            ->andWhere('p.quantity <= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('p.quantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function calculateTotalStockValue(): float
    {
        return (float) $this
            ->createQueryBuilder('p')
            ->select('SUM(p.price * p.quantity)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByFilters(?string $search, ?Category $category): array
    {
        $qb = $this->createQueryBuilder('p')->leftJoin('p.category', 'c')->addSelect('c');

        if ($search) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search)')->setParameter('search', '%' . $search . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :category')->setParameter('category', $category);
        }

        return $qb->orderBy('p.name', 'ASC')->getQuery()->getResult();
    }
}
