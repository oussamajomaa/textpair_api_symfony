<?php

namespace App\Repository;

use App\Entity\Alignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alignment>
 */
class AlignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alignment::class);
    }

    //    /**
    //     * @return Alignment[] Returns an array of Alignment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Alignment
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function countBySourceAuthor()
    {
        return $this->createQueryBuilder('a')
            ->select('a.source_author, COUNT(a.id) as count')  // Sélectionnez la source_author et le count
            ->groupBy('a.source_author')                       // Groupement par le champ source_author
            ->getQuery()
            ->getResult();
    }

    public function countByTargetAuthor()
    {
        return $this->createQueryBuilder('a')
            ->select('a.target_author, COUNT(a.id) as count')  // Sélectionnez la target_author et le count
            ->groupBy('a.target_author')                       // Groupement par le champ source_author
            ->getQuery()
            ->getResult();
    }
}
