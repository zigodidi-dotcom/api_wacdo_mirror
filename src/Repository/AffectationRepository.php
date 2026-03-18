<?php

namespace App\Repository;

use App\Entity\Affectation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function findByFilters($filters,  int $page = 1, int $limit = 10): array
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
            $qb->andWhere('f.id = :fonction')
                ->setParameter('fonction', $filters['fonction']);
        }

        if (!empty($filters['collaborateur'])) {
            $qb->andWhere('c.id = :collaborateur')
                ->setParameter('collaborateur', $filters['collaborateur']);
        }

        if (!empty($filters['restaurant'])) {
            $qb->andWhere('r.id = :restaurant')
                ->setParameter('restaurant', $filters['restaurant']);
        }

        if ($filters['status'] !== null) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery(), true);

        return [
            'data' => iterator_to_array($paginator->getIterator()),
            'total' => count($paginator),
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil(count($paginator) / $limit),
        ];

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
