<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Collaborateur;
use App\Repository\CollaborateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class CollaborateurRepositoryTest extends TestCase
{
    public function testUpgradePasswordThrowsForUnsupportedUser(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $registry
            ->method('getManagerForClass')
            ->with(Collaborateur::class)
            ->willReturn($em);

        $em
            ->method('getClassMetadata')
            ->with(Collaborateur::class)
            ->willReturn(new ClassMetadata(Collaborateur::class));

        $repo = new CollaborateurRepository($registry);

        $user = new class implements PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string
            {
                return 'old-hash';
            }
        };


        try{
            $repo->upgradePassword($user, 'new-hash');
            $this->fail('Expected UnsupportedUserException was not thrown.');
        } catch (UnsupportedUserException $e) {
//            dump($e->getMessage());
            $this->assertStringContainsString('not supported', $e->getMessage());
        }

    }

    public function testUpgradePasswordPersistsAndFlushesForCollaborateur(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $registry
            ->method('getManagerForClass')
            ->with(Collaborateur::class)
            ->willReturn($em);

        $em
            ->method('getClassMetadata')
            ->with(Collaborateur::class)
            ->willReturn(new ClassMetadata(Collaborateur::class));

        $repo = new CollaborateurRepository($registry);

        $collaborateur = $this->createMock(Collaborateur::class);

        $collaborateur
            ->expects(self::once())
            ->method('setPassword')
            ->with('new-hash');

        $em
            ->expects(self::once())
            ->method('persist')
            ->with($collaborateur);

        $em
            ->expects(self::once())
            ->method('flush');

        $repo->upgradePassword($collaborateur, 'new-hash');
    }
}
