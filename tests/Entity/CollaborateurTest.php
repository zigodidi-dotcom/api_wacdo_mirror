<?php

namespace App\Tests\Entity;

use App\Entity\Affectation;
use App\Entity\Collaborateur;
use PHPUnit\Framework\TestCase;

final class CollaborateurTest extends TestCase
{

    // Vérifier que les getters et setters de Collaborateur fonctionnent correctement, ainsi que la méthode __toString() et getUserIdentifier().
    public function testGettersSettersAndIdentifier(): void
    {
        $c = new Collaborateur();

        self::assertNull($c->getId());
        self::assertSame('', (string) $c, '__toString() should return empty string when id is null');

        $c->setEmail('john.doe@example.com');
        $c->setPrenom('John');
        $c->setNom('Doe');
        $c->setPassword('plain-or-hashed-does-not-matter-here');

        self::assertSame('john.doe@example.com', $c->getEmail());
        self::assertSame('john.doe@example.com', $c->getUserIdentifier());
        self::assertSame('John', $c->getPrenom());
        self::assertSame('Doe', $c->getNom());
        self::assertSame('plain-or-hashed-does-not-matter-here', $c->getPassword());
    }

    //  Vérifier que la liste des rôles d’un Collaborateur contient toujours au moins ROLE_USER et que les rôles sont uniques (pas de doublons).
    public function testRolesAlwaysContainRoleUserAndAreUnique(): void
    {
        $c = new Collaborateur();

        $c->setRoles([]);
        self::assertContains('ROLE_USER', $c->getRoles());

        $c->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $roles = $c->getRoles();

        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ADMIN', $roles);

        // La liste des rôles doit être unique
        self::assertSame($roles, array_values(array_unique($roles)));
    }

    // Vérifier que la méthode __serialize() hache correctement le mot de passe avec l’algorithme crc32c avant de le stocker dans le tableau sérialisé.
    public function testSerializeHashesPasswordWithCrc32c(): void
    {
        $c = new Collaborateur();
        $c->setPassword('my-secret-hash');

        $data = $c->__serialize();

        $passwordKey = "\0" . Collaborateur::class . "\0password";
        self::assertArrayHasKey($passwordKey, $data);
        self::assertSame(hash('crc32c', 'my-secret-hash'), $data[$passwordKey]);
    }

    //  Vérifier que l’ajout et la suppression d’une Affectation maintiennent correctement la relation côté "owning side" (côté Affectation).
    public function testAddAndRemoveAffectationMaintainsOwningSide(): void
    {
        $c = new Collaborateur();
        $a = new Affectation();

        self::assertCount(0, $c->getAffectations());

        $c->addAffectation($a);

        self::assertCount(1, $c->getAffectations());
        self::assertSame($c, $a->getCollaborateur(), 'addAffectation() should set the owning side (Affectation::collaborateur)');

        // Calling add twice should not duplicate
        $c->addAffectation($a);
        self::assertCount(1, $c->getAffectations());

        $c->removeAffectation($a);

        self::assertCount(0, $c->getAffectations());
        self::assertNull($a->getCollaborateur(), 'removeAffectation() should null the owning side when it still points to this collaborator');
    }
}
