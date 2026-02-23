<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Fonction;
use App\Repository\FonctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FonctionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FonctionRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Fonction::class);

        $this->purgeFonctions();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->em->close();
    }

    public function testFindReturnsPersistedFonction(): void
    {
        $fonction = (new Fonction())->setNom('Serveur');
        $this->em->persist($fonction);
        $this->em->flush();

        $id = $fonction->getId();
        self::assertNotNull($id);

        $this->em->clear();

        $found = $this->repository->find($id);

        self::assertInstanceOf(Fonction::class, $found);
        self::assertSame($id, $found->getId());
        self::assertSame('Serveur', $found->getNom());
    }

    public function testFindOneByReturnsFonctionByNom(): void
    {
        $fonction = (new Fonction())->setNom('Manager');
        $this->em->persist($fonction);
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findOneBy(['nom' => 'Manager']);

        self::assertInstanceOf(Fonction::class, $found);
        self::assertSame('Manager', $found->getNom());
    }

    public function testFindAllReturnsAllFonctions(): void
    {
        $this->em->persist((new Fonction())->setNom('Cuisine'));
        $this->em->persist((new Fonction())->setNom('Salle'));
        $this->em->flush();
        $this->em->clear();

        $all = $this->repository->findAll();

        self::assertCount(2, $all);
        self::assertSame(['Cuisine', 'Salle'], array_map(static fn (Fonction $f) => $f->getNom(), $all));
    }

    private function purgeFonctions(): void
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();

        // Nettoyage simple (attention si FK sur affectations : dans ce cas, il faut d'abord purger affectation)
        // MySQL refuses TRUNCATE on tables referenced by FKs (even if empty).
        // For test cleanup, temporarily disable FK checks to keep truncation fast and reliable.
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $connection->executeStatement($platform->getTruncateTableSQL('affectation', true));
            $connection->executeStatement($platform->getTruncateTableSQL('fonction', true));
        } finally {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }


    }
}
