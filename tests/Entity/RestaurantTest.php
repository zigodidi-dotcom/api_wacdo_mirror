<?php

namespace App\Tests\Entity;

use App\Entity\Affectation;
use App\Entity\Restaurant;
use PHPUnit\Framework\TestCase;

final class RestaurantTest extends TestCase
{

    // Vérifier que les getters et setters de l’entité Restaurant fonctionnent correctement.
    public function testGettersAndSetters(): void
    {
        $restaurant = new Restaurant();

        self::assertNull($restaurant->getId());
        self::assertNull($restaurant->getNom());
        self::assertNull($restaurant->getAdresse());
        self::assertNull($restaurant->getCodePostal());
        self::assertNull($restaurant->getVille());

        $restaurant
            ->setNom('Wacdo Centre')
            ->setAdresse('1 rue de la Frite')
            ->setCodePostal(75001)
            ->setVille('Paris');

        self::assertSame('Wacdo Centre', $restaurant->getNom());
        self::assertSame('1 rue de la Frite', $restaurant->getAdresse());
        self::assertSame(75001, $restaurant->getCodePostal());
        self::assertSame('Paris', $restaurant->getVille());
    }

    // Vérifier que la collection affectations est bien initialisée et vide à la création de l’entité.
    public function testAffectationsCollectionIsInitialized(): void
    {
        $restaurant = new Restaurant();

        self::assertCount(0, $restaurant->getAffectations());
    }


    // Vérifier que l’ajout d’une Affectation à un Restaurant met bien à jour la relation côté "owning side" (côté Affectation).
    public function testAddAffectationSetsOwningSide(): void
    {
        $restaurant = new Restaurant();
        $affectation = new Affectation();

        self::assertNull($affectation->getRestaurant());

        $restaurant->addAffectation($affectation);

        self::assertCount(1, $restaurant->getAffectations());
        self::assertTrue($restaurant->getAffectations()->contains($affectation));
        self::assertSame($restaurant, $affectation->getRestaurant());
    }

    // Vérifier que l’ajout de la même Affectation plusieurs fois ne la duplique pas dans la collection.
    public function testAddAffectationIsIdempotent(): void
    {
        $restaurant = new Restaurant();
        $affectation = new Affectation();

        $restaurant->addAffectation($affectation);
        $restaurant->addAffectation($affectation);

        self::assertCount(1, $restaurant->getAffectations());
    }

    // Vérifier que la suppression d’une Affectation met bien à jour la relation côté "owning side" (côté Affectation).
    public function testRemoveAffectationUnsetsOwningSide(): void
    {
        $restaurant = new Restaurant();
        $affectation = new Affectation();

        $restaurant->addAffectation($affectation);
        self::assertSame($restaurant, $affectation->getRestaurant());

        $restaurant->removeAffectation($affectation);

        self::assertCount(0, $restaurant->getAffectations());
        self::assertFalse($restaurant->getAffectations()->contains($affectation));
        self::assertNull($affectation->getRestaurant());
    }
}
