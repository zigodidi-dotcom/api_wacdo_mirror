<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Restaurant;
use App\Repository\CollaborateurRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RestaurantControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
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

    public function testListRestaurants(): void
    {

        $token = $this->getNoAdminJwtToken($this->client);

        $this->client->request(
            'GET',
            '/api/restaurant',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }

    public function testGetRestaurantDetails(): void
    {
        $restaurant = $this->createRestaurantForTest();

        $token = $this->getNoAdminJwtToken($this->client);

        $this->client->request(
            'GET',
            '/api/restaurant/' . $restaurant->getId() ,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }

    public function testCreateRestaurantRequiresAdmin(): void
    {

        $token = $this->getNoAdminJwtToken($this->client);

        $this->client->request(
            'POST',
            '/api/restaurant',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
                ],
            content: json_encode([
                 'nom' => 'Restaurant Test',
                 'adresse' => 'ra_test',
                 'code_postal' => '38080',
                 'ville' => 'rv_test',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertTrue(
            in_array($this->client->getResponse()->getStatusCode(), [401, 403], true),
            'La création doit être refusée sans ROLE_ADMIN (401 ou 403 attendu).'
        );
    }

    public function testAdminCanCreateRestaurant(): void
    {

        $token = $this->getAdminJwtToken($this->client);

        $this->client->request(
            'POST',
            '/api/restaurant',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: json_encode([
                'nom' => 'Restaurant Test',
                'adresse' => 'ra_test',
                'code_postal' => '38080',
                'ville' => 'rv_test',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }

    public function testAdminCanPatchRestaurant(): void
    {
        $restaurant = $this->createRestaurantForTest();
        $token = $this->getAdminJwtToken($this->client);

        $this->client->request(
            'PATCH',
            '/api/restaurant/' . $restaurant->getId(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: json_encode([
                'nom' => 'r_test_2',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
    }

    public function testAdminCanDeleteRestaurant(): void
    {
        $restaurant = $this->createRestaurantForTest();

        $token = $this->getAdminJwtToken($this->client);

        $this->client->request(
            'DELETE',
            '/api/restaurant/' . $restaurant->getId(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        self::assertResponseStatusCodeSame(204);
    }

    private function createRestaurantForTest(): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant
            ->setNom('r_test')
            ->setAdresse('ra_test')
            ->setCodePostal('38080')
            ->setVille('rv_test');

        $this->em->persist($restaurant);
        $this->em->flush();

        return $restaurant;
    }

    private function getAdminJwtToken($client): string
    {

        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'testadmin@test.fr',
                'password' => 'mdp123',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode($client->getResponse()->getContent(), true);

        // Format Lexik courant : { "token": "..." }
        self::assertIsArray($data);
        self::assertArrayHasKey('token', $data);
        self::assertIsString($data['token']);
        self::assertNotSame('', $data['token']);

        return $data['token'];
    }

    private function getNoAdminJwtToken($client): string
    {

        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'testnoadmin@test.fr',
                'password' => 'mdp123',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode($client->getResponse()->getContent(), true);

        // Format Lexik courant : { "token": "..." }
        self::assertIsArray($data);
        self::assertArrayHasKey('token', $data);
        self::assertIsString($data['token']);
        self::assertNotSame('', $data['token']);

        return $data['token'];
    }
}
