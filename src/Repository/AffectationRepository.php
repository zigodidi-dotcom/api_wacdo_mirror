<?php

namespace App\Repository;

use App\Entity\Affectation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Affectation>
 */
class AffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affectation::class);
    }

    public function findByFilters($filters): array
    {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.restaurant', 'r')
            ->innerJoin('a.fonction', 'f')
            ->innerJoin('a.collaborateur', 'c')
            ->addSelect( 'r') // Sélectionne uniquement les champs/champs liés
            ->addSelect( 'f') // Sélectionne uniquement les champs/champs liés
            ->addSelect( 'c'); // Sélectionne uniquement les champs/champs liés

        //!! compléter les tests phpunit si on ajoute des filtres
        if (!empty($filters['fonction'])) {
            $qb->andWhere('f.nom = :fonction')
                ->setParameter('fonction', $filters['fonction']);
        }

        if (!empty($filters['restaurant'])) {
            $qb->andWhere('r.nom = :restaurant')
                ->setParameter('restaurant', $filters['restaurant']);
        }

        if ($filters['status'] !== null) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }




        return $qb ->getQuery()
            ->getArrayResult(); // ou getArrayResult() si tu veux un tableau

    }

    public function findOne($value)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.restaurant', 'r')
            ->innerJoin('a.fonction', 'f')
            ->innerJoin('a.collaborateur', 'c')
            ->addSelect( 'r') // Sélectionne uniquement les champs/champs liés
            ->addSelect( 'f') // Sélectionne uniquement les champs/champs liés
            ->addSelect( 'c') // Sélectionne uniquement les champs/champs lié
            ->andWhere('a.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult(); // ou getArrayResult() si tu veux un tableau

    }

}
