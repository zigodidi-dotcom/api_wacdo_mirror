<?php



namespace App\Tests\Functional\Repository;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RestaurantRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RestaurantRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Restaurant::class);

        $this->purgeTables();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }

    public function testFindReturnsPersistedRestaurant(): void
    {
        $restaurant = (new Restaurant())
            ->setNom('Resto Test')
            ->setAdresse('Adresse test')
            ->setCodePostal(99999)
            ->setVille('VilleTest');

        $this->em->persist($restaurant);
        $this->em->flush();

        $id = $restaurant->getId();
        self::assertNotNull($id);

        $this->em->clear();

        $found = $this->repository->find($id);

        self::assertInstanceOf(Restaurant::class, $found);
        self::assertSame($id, $found->getId());
        self::assertSame('Resto Test', $found->getNom());
        self::assertSame('Adresse test', $found->getAdresse());
        self::assertSame(99999, $found->getCodePostal());
        self::assertSame('VilleTest', $found->getVille());
    }

    public function testFindOneByReturnsRestaurantByNom(): void
    {
        $this->em->persist(
            (new Restaurant())
                ->setNom('Le Gourmet')
                ->setAdresse('1 rue du Test')
                ->setCodePostal(75001)
                ->setVille('Paris')
        );
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findOneBy(['nom' => 'Le Gourmet']);

        self::assertInstanceOf(Restaurant::class, $found);
        self::assertSame('Le Gourmet', $found->getNom());
    }

    public function testFindAllReturnsAllRestaurants(): void
    {
        $this->em->persist(
            (new Restaurant())
                ->setNom('Resto A')
                ->setAdresse('Adresse A')
                ->setCodePostal(11111)
                ->setVille('VilleA')
        );

        $this->em->persist(
            (new Restaurant())
                ->setNom('Resto B')
                ->setAdresse('Adresse B')
                ->setCodePostal(22222)
                ->setVille('VilleB')
        );

        $this->em->flush();
        $this->em->clear();

        $all = $this->repository->findAll();

        self::assertCount(2, $all);
        self::assertSame(['Resto A', 'Resto B'], array_map(
            static fn (Restaurant $r): ?string => $r->getNom(),
            $all
        ));
    }

    private function purgeTables(): void
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();

        // Important: affectation référence restaurant (FK), donc on purge affectation d'abord.
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $connection->executeStatement($platform->getTruncateTableSQL('affectation', true));
            $connection->executeStatement($platform->getTruncateTableSQL('restaurant', true));
        } finally {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
