<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Collaborateur;
use App\Repository\CollaborateurRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CollaborateurControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private CollaborateurRepository $collaborateurRepository;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->collaborateurRepository = $container->get(CollaborateurRepository::class);

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

    public function testListCollaborateursRequiresAdmin(): void
    {

        $token = $this->getNoAdminJwtToken($this->client);

        $this->client->request(
            'GET',
            '/api/collaborateur',
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

    public function testAdminCanListCollaborateurs(): void
    {

        $token = $this->getAdminJwtToken($this->client);

        $this->client->request(
            'GET',
            '/api/collaborateur',
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

    public function testAdminCanFilterCollaborateurs(): void
    {

        $token = $this->getAdminJwtToken($this->client);

        // Sans filtres (doit rester OK)
        $this->client->request(
            'GET',
            '/api/collaborateur/filter',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        self::assertIsArray(json_decode($this->client->getResponse()->getContent(), true));

        // Avec filtres (même si ça ne matche rien, on attend un array JSON + 200)
        $this->client->request(
            'GET',
            '/api/collaborateur/filter?restaurant=does-not-exist&fonction=does-not-exist',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        self::assertIsArray(json_decode($this->client->getResponse()->getContent(), true));
    }

    public function testGetCollaborateurDetailsAsSelfIsAllowed(): void
    {

        $token = $this->getNoAdminJwtToken($this->client);

        $me = $this->collaborateurRepository->findOneBy(['email' => 'testnoadmin@test.fr']);
        self::assertNotNull($me, 'Le collaborateur de test "testnoadmin@test.fr" doit exister via les fixtures.');

        $this->client->request(
            'GET',
            '/api/collaborateur/' . $me->getId(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertSame($me->getId(), $data['id']);
    }

    public function testGetCollaborateurDetailsAsOtherNonAdminIsForbidden(): void
    {

        $token = $this->getNoAdminJwtToken($this->client);

        $admin = $this->collaborateurRepository->findOneBy(['email' => 'testadmin@test.fr']);
        self::assertNotNull($admin, 'Le collaborateur admin "testadmin@test.fr" doit exister via les fixtures.');

        $this->client->request(
            'GET',
            '/api/collaborateur/' . $admin->getId(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanGetCollaborateurDetails(): void
    {

        $token = $this->getAdminJwtToken($this->client);

        $someone = $this->collaborateurRepository->findOneBy(['email' => 'testnoadmin@test.fr']);
        self::assertNotNull($someone);

        $this->client->request(
            'GET',
            '/api/collaborateur/' . $someone->getId(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
    }

    public function testCreateCollaborateurRequiresAdmin(): void
    {

        $token = $this->getNoAdminJwtToken($this->client);

        $this->client->request(
            'POST',
            '/api/collaborateur',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'email' => 'new-collab@test.fr',
                'roles' => ['ROLE_USER'],
                'password' => 'Str0ng!Password_Str0ng!Password',
                'prenom' => 'New',
                'nom' => 'Collab',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertTrue(
            in_array($this->client->getResponse()->getStatusCode(), [401, 403], true),
            'La création doit être refusée sans ROLE_ADMIN (401 ou 403 attendu).'
        );
    }

    public function testAdminCanCreateCollaborateur(): void
    {

        $token = $this->getAdminJwtToken($this->client);

        $uniqueEmail = 'collab+' . uniqid('', true) . '@test.fr';

        $this->client->request(
            'POST',
            '/api/collaborateur',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'email' => $uniqueEmail,
                'password' => 'Str0ng!Password_Str0ng!Password',
                'prenom' => 'Prenom',
                'nom' => 'Nom',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);

        $created = $this->collaborateurRepository->findOneBy(['email' => $uniqueEmail]);
        self::assertNotNull($created, 'Le collaborateur doit être persisté en base.');
    }

    public function testPatchCollaborateurAsSelfIsAllowed(): void
    {

        $token = $this->getNoAdminJwtToken($this->client);

        $me = $this->collaborateurRepository->findOneBy(['email' => 'testnoadmin@test.fr']);
        self::assertNotNull($me);

        $this->client->request(
            'PATCH',
            '/api/collaborateur/' . $me->getId(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'prenom' => 'UpdatedPrenom',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
    }

    public function testPatchCollaborateurAsOtherNonAdminIsForbidden(): void
    {

        $token = $this->getNoAdminJwtToken($this->client);

        $admin = $this->collaborateurRepository->findOneBy(['email' => 'testadmin@test.fr']);
        self::assertNotNull($admin);

        $this->client->request(
            'PATCH',
            '/api/collaborateur/' . $admin->getId(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'prenom' => 'HackAttempt',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanDeleteCollaborateur(): void
    {

        $token = $this->getAdminJwtToken($this->client);

        $toDelete = $this->createCollaborateurForDeletion();
        self::assertNotNull($toDelete->getId());

        $this->client->request(
            'DELETE',
            '/api/collaborateur/' . $toDelete->getId(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseStatusCodeSame(204);

        $stillThere = $this->collaborateurRepository->find($toDelete->getId());
        self::assertNull($stillThere, 'Le collaborateur doit être supprimé.');
    }

    private function createCollaborateurForDeletion(): Collaborateur
    {
        $email = 'delete+' . uniqid('', true) . '@test.fr';

        $collab = new Collaborateur();
        $collab
            ->setEmail($email)
            ->setRoles(['ROLE_USER'])
            ->setPassword('plain-password-not-used-in-this-test')
            ->setPrenom('To')
            ->setNom('Delete');

        $this->em->persist($collab);
        $this->em->flush();

        return $collab;
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
