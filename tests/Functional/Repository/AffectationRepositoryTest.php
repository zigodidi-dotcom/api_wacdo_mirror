<?php


namespace App\Tests\Functional\Repository;

use App\Entity\Affectation;
use App\Entity\Collaborateur;
use App\Entity\Fonction;
use App\Entity\Restaurant;
use App\Repository\AffectationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Le test ideal est avec les fixtures
 * Pour le projet je realise un test avec les transactions
 */

final class AffectationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private AffectationRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(AffectationRepository::class);

        $this->purgeFonctions();

    }

    public function testFindByFiltersFiltersByFonctionName(): void
    {
        //Start transaction
        $this->em->beginTransaction();

        $fonctionChef = (new Fonction())->setNom('Chef');
        $fonctionServeur = (new Fonction())->setNom('Serveur');

        $restaurant = (new Restaurant())
            ->setNom('Resto Test')
            ->setAdresse('adresse test')
            ->setCodePostal(99999)
            ->setVille('villeTest');

        $collaborateur = (new Collaborateur())
            ->setNom('lnametest')
            ->setPrenom('fnametest')
            ->setEmail('test@free.fr')
            ->setPassword('test');

        $a1 = (new Affectation())
            ->setFonction($fonctionChef)
            ->setRestaurant($restaurant)
            ->setCollaborateur($collaborateur);

        $a2 = (new Affectation())
            ->setFonction($fonctionServeur)
            ->setRestaurant($restaurant)
            ->setCollaborateur($collaborateur);

        $this->em->persist($fonctionChef);
        $this->em->persist($fonctionServeur);
        $this->em->persist($restaurant);
        $this->em->persist($collaborateur);
        $this->em->persist($a1);
        $this->em->persist($a2);
        $this->em->flush();
        $this->em->clear();

        // Act
        $result = $this->repository->findByFilters([
            'fonction' => 'Chef',
        ]);

        // Assert
        self::assertCount(1, $result);

        // Comme le Repository fait ->getArrayResult(), on valide la structure minimale
        self::assertArrayHasKey('fonction', $result[0]);
        self::assertSame('Chef', $result[0]['fonction']['nom']);

        //end transaction
        $this->em->rollback();

    }

    public function testFindByFiltersWithEmptyFiltersReturnsAll(): void
    {
        //Start transaction
        $this->em->beginTransaction();

        // Creation pour le test
        $fonction = (new Fonction())->setNom('Chef');
        $restaurant = (new Restaurant())
            ->setNom('Resto Test')
            ->setAdresse('adresse test')
            ->setCodePostal(99999)
            ->setVille('villeTest');

        $collaborateur = (new Collaborateur())
            ->setNom('lnametest')
            ->setPrenom('fnametest')
            ->setEmail('test@free.fr')
            ->setPassword('test');

        $a1 = (new Affectation())
            ->setFonction($fonction)
            ->setRestaurant($restaurant)
            ->setCollaborateur($collaborateur);

        $a2 = (new Affectation())
            ->setFonction($fonction)
            ->setRestaurant($restaurant)
            ->setCollaborateur($collaborateur);

        $this->em->persist($fonction);
        $this->em->persist($restaurant);
        $this->em->persist($collaborateur);
        $this->em->persist($a1);
        $this->em->persist($a2);
        $this->em->flush();
        $this->em->clear();

        // Act
        $result = $this->repository->findByFilters([]);

        // Assert
        self::assertGreaterThanOrEqual(2, $result);

        // End transaction
        $this->em->rollback();
    }

    public function testFindReturnsPersistedAffectation(): void
    {
        //Start transaction
        $this->em->beginTransaction();

        // Creation pour le test
        $fonction = (new Fonction())->setNom('Chef');
        $restaurant = (new Restaurant())
            ->setNom('Resto Test')
            ->setAdresse('adresse test')
            ->setCodePostal(99999)
            ->setVille('villeTest');

        $collaborateur = (new Collaborateur())
            ->setNom('lnametest')
            ->setPrenom('fnametest')
            ->setEmail('test@free.fr')
            ->setPassword('test');

        $a1 = (new Affectation())
            ->setFonction($fonction)
            ->setRestaurant($restaurant)
            ->setCollaborateur($collaborateur);



        $this->em->persist($fonction);
        $this->em->persist($restaurant);
        $this->em->persist($collaborateur);
        $this->em->persist($a1);
        $this->em->flush();

        $id = $a1->getId();
        self::assertNotNull($id);

        $this->em->clear();

        $found = $this->repository->find($id);

        self::assertInstanceOf(Affectation::class, $found);
        self::assertSame($id, $found->getId());
        self::assertSame('Chef', $found->getFonction()->getNom());

        // End transaction
        $this->em->rollback();

    }

    protected function tearDown(): void
    {
        // Nettoyer la base de données après chaque test
        parent::tearDown();
        $this->em->close();
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
            $connection->executeStatement($platform->getTruncateTableSQL('collaborateur', true));
            $connection->executeStatement($platform->getTruncateTableSQL('restaurant', true));
        } finally {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }


    }

}
