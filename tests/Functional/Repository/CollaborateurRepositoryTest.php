<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\DataFixtures\AppFixtures;
use App\Entity\Collaborateur;
use App\Repository\CollaborateurRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * test avec fixtures
 */
final class CollaborateurRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CollaborateurRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(CollaborateurRepository::class);

        $this->loadDoctrineFixtures();
        $this->em->clear();
    }

    private function loadDoctrineFixtures(): void
    {
        $purger = new ORMPurger($this->em);
        $executor = new ORMExecutor($this->em, $purger);

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Charge les fixtures applicatives (src/DataFixtures/AppFixtures.php)
        $executor->execute([
            new AppFixtures($hasher),
        ]);
    }

    public function testFindOneReturnsCollaborateurWithAffectationsRestaurantAndFonction(): void
    {
        $any = $this->repository->findOneBy([]);
        self::assertInstanceOf(Collaborateur::class, $any);
        self::assertNotNull($any->getId());

        $result = $this->repository->findOne($any->getId());

        self::assertInstanceOf(Collaborateur::class, $result);
        self::assertSame($any->getId(), $result->getId());

        $affectations = $result->getAffectations();
        self::assertGreaterThanOrEqual(1, $affectations->count());

        $firstAffectation = $affectations->first();
        self::assertNotFalse($firstAffectation);

        self::assertNotNull($firstAffectation->getRestaurant());
        self::assertSame('Au palais gourmand_t', $firstAffectation->getRestaurant()->getNom());

        self::assertNotNull($firstAffectation->getFonction());
        self::assertSame('Equipier_t', $firstAffectation->getFonction()->getNom());
    }

    public function testFindByFiltersFiltersByFonctionName(): void
    {
        $result = $this->repository->findByFilters([
            'fonction' => 'Equipier_t',
        ]);

        self::assertCount(5, $result);
        foreach ($result as $collaborateur) {
            self::assertInstanceOf(Collaborateur::class, $collaborateur);
            self::assertGreaterThanOrEqual(1, $collaborateur->getAffectations()->count());
        }
    }

    public function testFindByFiltersFiltersByRestaurantName(): void
    {
        $result = $this->repository->findByFilters([
            'restaurant' => 'Au palais gourmand_t',
        ]);

        self::assertCount(5, $result);
        foreach ($result as $collaborateur) {
            self::assertInstanceOf(Collaborateur::class, $collaborateur);

            $firstAffectation = $collaborateur->getAffectations()->first();
            self::assertNotFalse($firstAffectation);
            self::assertSame('Au palais gourmand_t', $firstAffectation->getRestaurant()->getNom());
        }
    }

    public function testFindByFiltersWithNonExistingFonctionReturnsEmpty(): void
    {
        $result = $this->repository->findByFilters([
            'fonction' => 'Manager_t',
        ]);

        self::assertCount(0, $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
