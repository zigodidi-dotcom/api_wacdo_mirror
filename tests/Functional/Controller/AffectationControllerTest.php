<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Affectation;
use App\Entity\Collaborateur;
use App\Entity\Fonction;
use App\Entity\Restaurant;
use App\Repository\AffectationRepository;
use App\Repository\CollaborateurRepository;
use App\Repository\FonctionRepository;
use App\Repository\RestaurantRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AffectationControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private AffectationRepository $affectationRepository;
    private CollaborateurRepository $collaborateurRepository;
    private RestaurantRepository $restaurantRepository;
    private FonctionRepository $fonctionRepository;
    private $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        $this->affectationRepository = $container->get(AffectationRepository::class);
        $this->collaborateurRepository = $container->get(CollaborateurRepository::class);
        $this->restaurantRepository = $container->get(RestaurantRepository::class);
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

    public function testListAffectationsRequiresAdmin(): void
    {
        $token = $this->getNoAdminJwtToken($this->client);

        $this->client->request(
            'GET',
            '/api/affectation',
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

    public function testAdminCanListAffectations(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        $this->client->request(
            'GET',
            '/api/affectation',
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

    public function testAdminCanFilterAffectations(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        // Sans filtres -> 200 + array
        $this->client->request(
            'GET',
            '/api/affectation/filter',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        self::assertIsArray(json_decode($this->client->getResponse()->getContent(), true));

        // Avec filtre fonction connu des fixtures (peut renvoyer 0..n éléments, mais doit rester OK)
        $this->client->request(
            'GET',
            '/api/affectation/filter?fonction=Equipier_t',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        self::assertIsArray(json_decode($this->client->getResponse()->getContent(), true));
    }

    public function testCreateAffectationRequiresAdmin(): void
    {
        $token = $this->getNoAdminJwtToken($this->client);

        $collaborateur = $this->mustFindCollaborateur();
        $restaurant = $this->mustFindRestaurant();
        $fonction = $this->mustFindFonctionEquipier();

        $this->client->request(
            'POST',
            '/api/affectation',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'collaborateur' => $collaborateur->getId(),
                'restaurant' => $restaurant->getId(),
                'fonction' => $fonction->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        self::assertTrue(
            in_array($this->client->getResponse()->getStatusCode(), [401, 403], true),
            'La création doit être refusée sans ROLE_ADMIN (401 ou 403 attendu).'
        );
    }

    public function testAdminCanCreateAffectation(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        $collaborateur = $this->createCollaborateurForTest();
        $restaurant = $this->mustFindRestaurant();
        $fonction = $this->mustFindFonctionEquipier();

        $this->client->request(
            'POST',
            '/api/affectation',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'collaborateur' => $collaborateur->getId(),
                'restaurant' => $restaurant->getId(),
                'fonction' => $fonction->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);

        $this->em->clear();
        $created = $this->affectationRepository->find($data['id']);
        self::assertNotNull($created, 'L’affectation doit être persistée en base.');
    }

    public function testAdminCannotCreateDuplicateAffectationReturns409(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        // Les fixtures créent déjà des affectations avec:
        // restaurant = "Au palais gourmand_t", fonction = "Equipier_t", collaborateur = users[0..4]
        // On va réutiliser un de ces collaborateurs pour forcer le doublon.
        $existingCollab = $this->mustFindAnyFixtureCollaborateur();
        $restaurant = $this->mustFindRestaurant();
        $fonction = $this->mustFindFonctionEquipier();

        // 1ère création: peut déjà exister selon le collaborateur choisi; on force donc d'abord un doublon garanti:
        // on crée l'affectation si elle n'existe pas, puis on re-POST la même (et là on attend 409).
        $this->ensureAffectationExists($existingCollab, $restaurant, $fonction);

        $this->client->request(
            'POST',
            '/api/affectation',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'collaborateur' => $existingCollab->getId(),
                'restaurant' => $restaurant->getId(),
                'fonction' => $fonction->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(409);
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
    }

    public function testAdminCanPatchAffectation(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        $collaborateur = $this->createCollaborateurForTest();
        $restaurant = $this->mustFindRestaurant();
        $equipier = $this->mustFindFonctionEquipier();
        $manager = $this->mustFindFonctionManager();

        $affectation = $this->createAffectation($collaborateur, $restaurant, $equipier);

        $this->client->request(
            'PATCH',
            '/api/affectation/' . $affectation->getId(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'fonction' => $manager->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $this->em->clear();
        $reloaded = $this->affectationRepository->find($affectation->getId());
        self::assertNotNull($reloaded);
        self::assertSame($manager->getId(), $reloaded->getFonction()?->getId());
    }

    public function testAdminCanDeleteAffectation(): void
    {
        $token = $this->getAdminJwtToken($this->client);

        $collaborateur = $this->createCollaborateurForTest();
        $restaurant = $this->mustFindRestaurant();
        $fonction = $this->mustFindFonctionEquipier();

        $affectation = $this->createAffectation($collaborateur, $restaurant, $fonction);
        self::assertNotNull($affectation->getId());

        $this->client->request(
            'DELETE',
            '/api/affectation/' . $affectation->getId(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseStatusCodeSame(204);

        $this->em->clear();
        $stillThere = $this->affectationRepository->find($affectation->getId());
        self::assertNull($stillThere, 'L’affectation doit être supprimée.');
    }

    private function createAffectation(Collaborateur $collaborateur, Restaurant $restaurant, Fonction $fonction): Affectation
    {
        $affectation = new Affectation();
        $affectation
            ->setCollaborateur($collaborateur)
            ->setRestaurant($restaurant)
            ->setFonction($fonction);

        $this->em->persist($affectation);
        $this->em->flush();

        return $affectation;
    }

    private function createCollaborateurForTest(): Collaborateur
    {
        $uniqueEmail = 'affectation+' . uniqid('', true) . '@test.fr';

        $collab = new Collaborateur();
        $collab
            ->setEmail($uniqueEmail)
            ->setRoles(['ROLE_USER'])
            ->setPassword('plain-password-not-used-in-this-test')
            ->setPrenom('Prenom')
            ->setNom('Nom')
            ->setDateEmbauche(new \DateTime())
            ->setDerniereConnexion(new \DateTime());

        $this->em->persist($collab);
        $this->em->flush();

        return $collab;
    }

    private function mustFindRestaurant(): Restaurant
    {
        $restaurant = $this->restaurantRepository->findOneBy(['nom' => 'Au palais gourmand_t']);
        self::assertNotNull($restaurant, 'Le restaurant de fixture doit exister.');

        return $restaurant;
    }

    private function mustFindFonctionEquipier(): Fonction
    {
        $fonction = $this->fonctionRepository->findOneBy(['nom' => 'Equipier_t']);
        self::assertNotNull($fonction, 'La fonction "Equipier_t" doit exister en fixture.');

        return $fonction;
    }

    private function mustFindFonctionManager(): Fonction
    {
        $fonction = $this->fonctionRepository->findOneBy(['nom' => 'Manager_t']);
        self::assertNotNull($fonction, 'La fonction "Manager_t" doit exister en fixture.');

        return $fonction;
    }

    private function mustFindCollaborateur(): Collaborateur
    {
        $collaborateur = $this->collaborateurRepository->findOneBy(['email' => 'testnoadmin@test.fr']);
        self::assertNotNull($collaborateur, 'Le collaborateur "testnoadmin@test.fr" doit exister en fixture.');

        return $collaborateur;
    }

    private function mustFindAnyFixtureCollaborateur(): Collaborateur
    {
        // On tente un collaborateur "faker" (les 5 premiers), sinon on retombe sur le non-admin fixe.
        $any = $this->collaborateurRepository->findOneBy([]);
        self::assertNotNull($any, 'Au moins un collaborateur doit exister en base (fixtures).');

        return $any;
    }

    private function ensureAffectationExists(Collaborateur $collaborateur, Restaurant $restaurant, Fonction $fonction): void
    {
        $existing = $this->affectationRepository->findOneBy([
            'collaborateur' => $collaborateur,
            'restaurant' => $restaurant,
            'fonction' => $fonction,
        ]);

        if ($existing !== null) {
            return;
        }

        $this->createAffectation($collaborateur, $restaurant, $fonction);
        $this->em->clear();
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
