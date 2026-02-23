<?php


namespace App\Tests\Entity;

use App\Entity\Affectation;
use App\Entity\Collaborateur;
use App\Entity\Fonction;
use App\Entity\Restaurant;
use PHPUnit\Framework\TestCase;

final class AffectationTest extends TestCase
{

    //Vérifier que les propriétés de l’entité Affectation sont bien initialisées à null lors de sa création.
    public function testInitialState(): void
    {
        $affectation = new Affectation();

        self::assertNull($affectation->getId());
        self::assertNull($affectation->getRestaurant());
        self::assertNull($affectation->getFonction());
        self::assertNull($affectation->getCollaborateur());
    }

    //Vérifier que les getters et setters de l’entité Affectation fonctionnent correctement.
    public function testGettersAndSetters(): void
    {
        $affectation = new Affectation();

        $restaurant = new Restaurant();
        $fonction = new Fonction();
        $collaborateur = new Collaborateur();

        self::assertSame($affectation, $affectation->setRestaurant($restaurant));
        self::assertSame($restaurant, $affectation->getRestaurant());

        self::assertSame($affectation, $affectation->setFonction($fonction));
        self::assertSame($fonction, $affectation->getFonction());

        self::assertSame($affectation, $affectation->setCollaborateur($collaborateur));
        self::assertSame($collaborateur, $affectation->getCollaborateur());
    }

    //Vérifier que les relations peuvent être réinitialisées à null.
    public function testCanUnsetAssociationsWithNull(): void
    {
        $affectation = new Affectation();

        $affectation->setRestaurant(new Restaurant());
        $affectation->setFonction(new Fonction());
        $affectation->setCollaborateur(new Collaborateur());

        self::assertSame($affectation, $affectation->setRestaurant(null));
        self::assertNull($affectation->getRestaurant());

        self::assertSame($affectation, $affectation->setFonction(null));
        self::assertNull($affectation->getFonction());

        self::assertSame($affectation, $affectation->setCollaborateur(null));
        self::assertNull($affectation->getCollaborateur());
    }
}
