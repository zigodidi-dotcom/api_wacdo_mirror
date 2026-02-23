<?php

namespace App\Tests\Entity;

use App\Entity\Affectation;
use App\Entity\Fonction;
use PHPUnit\Framework\TestCase;

final class FonctionTest extends TestCase
{
    //Vérifier que le getter et le setter de la propriété nom fonctionnent correctement.
    public function testNomGetterSetter(): void
    {
        $fonction = new Fonction();

        self::assertNull($fonction->getNom());

        $returned = $fonction->setNom('Manager');

        self::assertSame($fonction, $returned, 'setNom() doit être fluide (return $this).');
        self::assertSame('Manager', $fonction->getNom());
    }

    // Vérifier que la collection affectations est bien initialisée et vide à la création de l’entité.
    public function testAffectationsCollectionIsInitialized(): void
    {
        $fonction = new Fonction();

        self::assertCount(0, $fonction->getAffectations());
    }

    // Vérifier que l’ajout d’une Affectation à une Fonction met bien à jour la relation côté "owning side" (côté Affectation).
    public function testAddAffectationSetsOwningSide(): void
    {
        $fonction = new Fonction();
        $affectation = new Affectation();

        self::assertNull($affectation->getFonction());

        $fonction->addAffectation($affectation);

        self::assertCount(1, $fonction->getAffectations());
        self::assertTrue($fonction->getAffectations()->contains($affectation));
        self::assertSame($fonction, $affectation->getFonction(), 'addAffectation() doit setter la fonction côté Affectation.');
    }

    // Vérifier que l’ajout de la même Affectation plusieurs fois ne la duplique pas dans la collection.
    public function testAddAffectationIsIdempotent(): void
    {
        $fonction = new Fonction();
        $affectation = new Affectation();

        $fonction->addAffectation($affectation);
        $fonction->addAffectation($affectation);

        self::assertCount(1, $fonction->getAffectations(), 'La même affectation ne doit pas être ajoutée deux fois.');
        self::assertSame($fonction, $affectation->getFonction());
    }

    //Vérifier que la suppression d’une Affectation met bien à jour la relation côté "owning side" (côté Affectation).
    public function testRemoveAffectationUnsetsOwningSideWhenItMatches(): void
    {
        $fonction = new Fonction();
        $affectation = new Affectation();

        $fonction->addAffectation($affectation);
        self::assertSame($fonction, $affectation->getFonction());

        $fonction->removeAffectation($affectation);

        self::assertCount(0, $fonction->getAffectations());
        self::assertFalse($fonction->getAffectations()->contains($affectation));
        self::assertNull($affectation->getFonction(), 'removeAffectation() doit mettre la fonction à null côté Affectation.');
    }

    // Vérifier que la suppression d’une Affectation ne modifie pas sa relation si elle pointe déjà vers une autre Fonction.
    public function testRemoveAffectationDoesNotNullFonctionIfAffectationPointsToAnotherFonction(): void
    {
        $fonction1 = new Fonction();
        $fonction2 = new Fonction();
        $affectation = new Affectation();

        $fonction1->addAffectation($affectation);
        self::assertSame($fonction1, $affectation->getFonction());

        $affectation->setFonction($fonction2);
        self::assertSame($fonction2, $affectation->getFonction());

        $fonction1->removeAffectation($affectation);

        self::assertSame(
            $fonction2,
            $affectation->getFonction(),
            'Si Affectation pointe déjà vers une autre Fonction, removeAffectation() ne doit pas l’écraser.'
        );
    }
}
