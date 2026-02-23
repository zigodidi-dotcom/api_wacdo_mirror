<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\AppFixtures;
use App\Repository\CollaborateurRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginControllerTest extends WebTestCase
{

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


    public function testLoginSuccessReturnsToken(): void
    {


        $this->client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'testadmin@test.fr',
                'password' => 'mdp123',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($data);
        self::assertArrayHasKey('token', $data);
        self::assertIsString($data['token']);
        self::assertNotSame('', $data['token']);

        // Optionnel: un JWT ressemble souvent à "xxx.yyy.zzz"
//        self::assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+=*\.[A-Za-z0-9\-_]+=*\.[A-Za-z0-9\-_]+=*$/', $data['token']);
    }

    public function testLoginWithBadCredentialsReturns401(): void
    {

        $this->client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'test@test.fr',
                'password' => 'wrong-password',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(401);
        self::assertResponseFormatSame('json');
    }
}
