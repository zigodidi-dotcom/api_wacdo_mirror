<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Fonction;
use App\Repository\FonctionRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class FonctionControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private FonctionRepository $fonctionRepository;
    private $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->fonctionRepository = $container->get(FonctionRepository::class);

        $this->loadDoctrineFixtures();
        $this->em->clear();
    }

    private function loadDoctrineFixtures(): void
    {
        $purger = new ORMPurger($this->em);
        $executor = new ORMExecutor($this->em, $purger);

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $executor->execute([
            new AppFixtures($hasher),
        ]);
    }

    public function testListFonctionsRequiresAdmin(): void
    {
        $token = $this->getNoAdminJwtToken($this->client);

        $this->client->request(
            'GET',
            '/api/fonctions',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertTrue(
            in_array($this->client->getResponse()->getStatusCode(), [401, 403], true),
            'La liste doit être refusée sans ROLE_ADMIN (401 ou 403 attendu).'
        );
    }

    public function testAdminCanListFonctions(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        $this->client->request(
            'GET',
            '/api/fonctions',
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

    public function testCreateFonctionRequiresAdmin(): void
    {
        $token = $this->getNoAdminJwtToken($this->client);

        $this->client->request(
            'POST',
            '/api/fonctions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'nom' => 'Fonction ' . uniqid('', true),
            ], JSON_THROW_ON_ERROR)
        );

        self::assertTrue(
            in_array($this->client->getResponse()->getStatusCode(), [401, 403], true),
            'La création doit être refusée sans ROLE_ADMIN (401 ou 403 attendu).'
        );
    }

    public function testAdminCanCreateFonction(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        $nom = 'Fonction ' . uniqid('', true);

        $this->client->request(
            'POST',
            '/api/fonctions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'nom' => $nom,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);

        $created = $this->fonctionRepository->find($data['id']);
        self::assertNotNull($created, 'La fonction doit être persistée en base.');
        self::assertSame($nom, $created->getNom());
    }

//    public function testAdminCannotCreateFonctionWithBlankNom(): void
//    {
//        $token = $this->getAdminJwtToken($this->client);
//
//        $this->client->request(
//            'POST',
//            '/api/fonctions',
//            server: [
//                'CONTENT_TYPE' => 'application/json',
//                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
//                'HTTP_ACCEPT' => 'application/json',
//            ],
//            content: json_encode([
//                'nom' => '',
//            ], JSON_THROW_ON_ERROR)
//        );
//
//        self::assertResponseStatusCodeSame(400);
//    }

    public function testUpdateFonctionRequiresAdmin(): void
    {
        $token = $this->getNoAdminJwtToken($this->client);

        $fonction = $this->createFonction('ToUpdate ' . uniqid('', true));

        $this->client->request(
            'PATCH',
            '/api/fonctions/' . $fonction->getId(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'nom' => 'Hacked',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertTrue(
            in_array($this->client->getResponse()->getStatusCode(), [401, 403], true),
            'La modification doit être refusée sans ROLE_ADMIN (401 ou 403 attendu).'
        );
    }

    public function testAdminCanPatchFonction(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        $fonction = $this->createFonction('Before ' . uniqid('', true));
        $newNom = 'After ' . uniqid('', true);

        $this->client->request(
            'PATCH',
            '/api/fonctions/' . $fonction->getId(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'nom' => $newNom,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $this->em->clear();
        $reloaded = $this->fonctionRepository->find($fonction->getId());
        self::assertNotNull($reloaded);
        self::assertSame($newNom, $reloaded->getNom());
    }

//    public function testAdminCannotPatchFonctionWithBlankNom(): void
//    {
//        $token = $this->getAdminJwtToken($this->client);
//
//        $fonction = $this->createFonction('Valid ' . uniqid('', true));
//
//        $this->client->request(
//            'PATCH',
//            '/api/fonctions/' . $fonction->getId(),
//            server: [
//                'CONTENT_TYPE' => 'application/json',
//                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
//                'HTTP_ACCEPT' => 'application/json',
//            ],
//            content: json_encode([
//                'nom' => '',
//            ], JSON_THROW_ON_ERROR)
//        );
//
//        self::assertResponseStatusCodeSame(400);
//    }

    public function testDeleteFonctionRequiresAdmin(): void
    {
        $token = $this->getNoAdminJwtToken($this->client);

        $fonction = $this->createFonction('ToDelete ' . uniqid('', true));

        $this->client->request(
            'DELETE',
            '/api/fonctions/' . $fonction->getId(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertTrue(
            in_array($this->client->getResponse()->getStatusCode(), [401, 403], true),
            'La suppression doit être refusée sans ROLE_ADMIN (401 ou 403 attendu).'
        );
    }

    public function testAdminCanDeleteFonction(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        $fonction = $this->createFonction('ToDelete ' . uniqid('', true));
        self::assertNotNull($fonction->getId());

        $this->client->request(
            'DELETE',
            '/api/fonctions/' . $fonction->getId(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseStatusCodeSame(204);

        $this->em->clear();
        $stillThere = $this->fonctionRepository->find($fonction->getId());
        self::assertNull($stillThere, 'La fonction doit être supprimée.');
    }

    private function createFonction(string $nom): Fonction
    {
        $fonction = new Fonction();
        $fonction->setNom($nom);

        $this->em->persist($fonction);
        $this->em->flush();

        return $fonction;
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
        self::assertIsArray($data);
        self::assertArrayHasKey('token', $data);
        self::assertIsString($data['token']);
        self::assertNotSame('', $data['token']);

        return $data['token'];
    }
}
