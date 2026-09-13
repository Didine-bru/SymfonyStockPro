<?php

namespace App\Repository;

use App\Entity\StockMovement;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockMovement>
 */
class StockMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockMovement::class);
    }
    public function findByFilters(?string $search, ?string $type): array
{
    $qb = $this->createQueryBuilder('m')
        ->leftJoin('m.product', 'p')
        ->addSelect('p')
        ->orderBy('m.createdAt', 'DESC');

    if ($search) {
        $qb
            ->andWhere('LOWER(p.name) LIKE LOWER(:search)')
            ->setParameter('search', '%' . $search . '%');
    }

    if ($type) {
        $qb
            ->andWhere('m.type = :type')
            ->setParameter('type', $type);
    }

    return $qb->getQuery()->getResult();
}
public function findByProduct(Product $product): array
{
    return $this->createQueryBuilder('m')
        ->andWhere('m.product = :product')
        ->setParameter('product', $product)
        ->orderBy('m.createdAt', 'ASC')
        ->addOrderBy('m.id', 'ASC')
        ->getQuery()
        ->getResult();
}

//    /**
//     * @return StockMovement[] Returns an array of StockMovement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?StockMovement
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
