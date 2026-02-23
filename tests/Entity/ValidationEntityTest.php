<?php

namespace App\Tests\Entity;

use App\Entity\Affectation;
use App\Entity\Collaborateur;
use App\Entity\Fonction;
use App\Entity\Restaurant;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidationEntityTest extends KernelTestCase
{
    private function validator(): ValidatorInterface
    {
        self::bootKernel();

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');

        return $validator;
    }

    public function testValidationNomFonctionNotBlank(): void
    {
        $fonction = new Fonction();
        $fonction->setNom(''); // NotBlank

        $violations = $this->validator()->validate($fonction);

        // Affiche toutes les violations
//        foreach ($violations as $violation) {
//            echo sprintf(
//                "Violation: %s (propriété: %s, valeur: %s)\n",
//                $violation->getMessage(),
//                $violation->getPropertyPath(),
//                $violation->getInvalidValue()
//            );
//        }

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('nom', $violations[0]->getPropertyPath());
        self::assertSame('Un nom de fonction est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationOkWhenNomFonctionProvided(): void
    {
        $fonction = new Fonction();
        $fonction->setNom('Manager');

        $violations = $this->validator()->validate($fonction);

        // Affiche toutes les violations
//        foreach ($violations as $violation) {
//            echo sprintf(
//                "Violation: %s (propriété: %s, valeur: %s)\n",
//                $violation->getMessage(),
//                $violation->getPropertyPath(),
//                $violation->getInvalidValue()
//            );
//        }

        self::assertSame(0, $violations->count());
    }

    public function testValidationNomRestaurantNotBlank(): void
    {
        $restaurant = new Restaurant();
        $restaurant->setNom('');

        $violations = $this->validator()->validate($restaurant);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('nom', $violations[0]->getPropertyPath());
        self::assertSame('Un nom de restaurant est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationAdresseRestaurantNotBlank(): void
    {
        $restaurant = (new Restaurant())
            ->setNom('Wacdo')
            ->setAdresse('')
            ->setCodePostal(75001)
            ->setVille('Paris');

        $violations = $this->validator()->validate($restaurant);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('adresse', $violations[0]->getPropertyPath());
        self::assertSame('Une adresse de restaurant est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationCodePostalRestaurantNotBlank(): void
    {
        $restaurant = new Restaurant()
            ->setNom('Wacdo')
            ->setAdresse('1 rue de la Frite')
            ->setVille('Paris');

        // on force null via reflection car setCodePostal() attend un int
        $ref = new \ReflectionProperty(Restaurant::class, 'code_postal');
        $ref->setAccessible(true);
        $ref->setValue($restaurant, null);

        $violations = $this->validator()->validate($restaurant);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('code_postal', $violations[0]->getPropertyPath());
        self::assertSame('Un code postal de restaurant est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationVilleRestaurantNotBlank(): void
    {
        $restaurant = new Restaurant()
            ->setNom('Wacdo')
            ->setAdresse('1 rue de la Frite')
            ->setCodePostal(75001)
            ->setVille('');

        $violations = $this->validator()->validate($restaurant);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('ville', $violations[0]->getPropertyPath());
        self::assertSame('Une ville pour le restaurant est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationRestaurantOkWhenAllRequiredFieldsProvided(): void
    {
        $restaurant = new Restaurant()
            ->setNom('Wacdo Centre')
            ->setAdresse('1 rue de la Frite')
            ->setCodePostal(75001)
            ->setVille('Paris');

        $violations = $this->validator()->validate($restaurant);

        self::assertSame(0, $violations->count());
    }

    public function testValidationEmailCollaborateurNotBlank(): void
    {
        $c = new Collaborateur();
        $c->setEmail('');
        $c->setPassword('Str0ngPassword!WithUniqChars123');
        $c->setPrenom('John');
        $c->setNom('Doe');

        $violations = $this->validator()->validate($c);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('email', $violations[0]->getPropertyPath());
        self::assertSame("l'email est obligatoire", $violations[0]->getMessage());
    }

    public function testValidationEmailCollaborateurFormat(): void
    {
        $c = new Collaborateur();
        $c->setEmail('not-an-email');
        $c->setPassword('Str0ngPassword!WithUniqChars123');
        $c->setPrenom('John');
        $c->setNom('Doe');

        $violations = $this->validator()->validate($c);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('email', $violations[0]->getPropertyPath());
        self::assertStringContainsString('ne respect pas un format valide', $violations[0]->getMessage());
    }

    public function testValidationPasswordCollaborateurNotBlank(): void
    {
        $c = new Collaborateur();
        $c->setEmail('john.doe@example.com');
        $c->setPrenom('John');
        $c->setNom('Doe');

        // on force null via reflection car setPassword() attend un string
        $ref = new \ReflectionProperty(Collaborateur::class, 'password');
        $ref->setAccessible(true);
        $ref->setValue($c, null);

        $violations = $this->validator()->validate($c);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('password', $violations[0]->getPropertyPath());
        self::assertSame('le mot de passe est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationPrenomCollaborateurNotBlank(): void
    {
        $c = new Collaborateur();
        $c->setEmail('john.doe@example.com');
        $c->setPassword('Str0ngPassword!WithUniqChars123');
        $c->setPrenom('');
        $c->setNom('Doe');

        $violations = $this->validator()->validate($c);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('prenom', $violations[0]->getPropertyPath());
        self::assertSame('le prenom est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationNomCollaborateurNotBlank(): void
    {
        $c = new Collaborateur();
        $c->setEmail('john.doe@example.com');
        $c->setPassword('Str0ngPassword!WithUniqChars123');
        $c->setPrenom('John');
        $c->setNom('');

        $violations = $this->validator()->validate($c);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('nom', $violations[0]->getPropertyPath());
        self::assertSame('le nom est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationCollaborateurOkWhenAllRequiredFieldsProvided(): void
    {
        $c = new Collaborateur();
        $c->setEmail('john.doe@example.com');
        $c->setPassword('Str0ngPassword!WithUniqChars123');
        $c->setPrenom('John');
        $c->setNom('Doe');

        $violations = $this->validator()->validate($c);

        self::assertSame(0, $violations->count());
    }

    public function testValidationAffectationRestaurantNotBlank(): void
    {
        $a = new Affectation();
        $a->setFonction(new Fonction());
        $a->setCollaborateur(new Collaborateur());
        $a->setRestaurant(null);

        $violations = $this->validator()->validate($a);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('restaurant', $violations[0]->getPropertyPath());
        // Message tel qu'écrit dans l'entité (même s'il semble inversé)
        self::assertSame('Un collaborateur est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationAffectationFonctionNotBlank(): void
    {
        $a = new Affectation();
        $a->setRestaurant(new Restaurant());
        $a->setCollaborateur(new Collaborateur());
        $a->setFonction(null);

        $violations = $this->validator()->validate($a);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('fonction', $violations[0]->getPropertyPath());
        self::assertSame('Une fonction est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationAffectationCollaborateurNotBlank(): void
    {
        $a = new Affectation();
        $a->setRestaurant(new Restaurant());
        $a->setFonction(new Fonction());
        $a->setCollaborateur(null);

        $violations = $this->validator()->validate($a);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('collaborateur', $violations[0]->getPropertyPath());
        self::assertSame('Un Restaurant est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationAffectationOkWhenAllRequiredAssociationsProvided(): void
    {
        $a = new Affectation();
        $a->setRestaurant(new Restaurant());
        $a->setFonction(new Fonction());
        $a->setCollaborateur(new Collaborateur());

        $violations = $this->validator()->validate($a);

        self::assertSame(0, $violations->count());
    }


}
