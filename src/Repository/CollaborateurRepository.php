<?php

namespace App\Repository;

use App\Entity\Collaborateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Collaborateur>
 */
class CollaborateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collaborateur::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Collaborateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findOne($value)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.affectations', 'a')
            ->leftJoin('a.restaurant', 'r')
            ->leftJoin('a.fonction', 'f')
            ->addSelect('a') // Sélectionne uniquement les champs/champs liés
            ->addSelect('r') // Sélectionne uniquement les champs/champs liés
            ->addSelect('f') // Sélectionne uniquement les champs/champs liés
            ->andWhere('c.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult(); // ou getArrayResult() si tu veux un tableau
        ;
    }

    public function findByFilters($filters): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.affectations', 'a')
            ->innerJoin('a.fonction', 'f')
            ->innerJoin('a.restaurant', 'r')
            ->addSelect('a') // Sélectionne uniquement les champs/champs liés
            ->addSelect('f') // Sélectionne uniquement les champs/champs liés
            ->addSelect('r'); // Sélectionne uniquement les champs/champs liés

        if (!empty($filters['fonction'])) {
            $qb->andWhere('f.nom = :fonction')
                ->setParameter('fonction', $filters['fonction']);
        }
        if (!empty($filters['restaurant'])) {
            $qb->andWhere('r.nom = :restaurant')
                ->setParameter('restaurant', $filters['restaurant']);
        }


        return $qb->getQuery()
            ->getResult(); // ou getArrayResult() si tu veux un tableau
        ;
    }


}
