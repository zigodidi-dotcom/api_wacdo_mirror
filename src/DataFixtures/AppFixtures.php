<?php

namespace App\DataFixtures;

use App\Entity\Affectation;
use App\Entity\Collaborateur;
use App\Entity\Fonction;
use App\Entity\Restaurant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(private UserPasswordHasherInterface $userPassword)
    {

    }
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        // $product = new Product();
        // $manager->persist($product);

        $users = [];
        for($i = 0; $i < 5; $i++){
            $user = new Collaborateur();

            $user
                -> setNom($faker->lastName())
                ->setEmail($faker->unique()->safeEmail())
                ->setPassword($this->userPassword->hashPassword($user, 'mdp123'))
                ->setPrenom($faker->firstName())
                ->setDateEmbauche(new \DateTime())
                ->setDerniereConnexion(new \DateTime());

            $users[] = $user;
            $manager->persist($user);
        }

        $userAdminTestFixe = new Collaborateur();
        $userAdminTestFixe
            -> setNom('lntest')
            ->setEmail('testadmin@test.fr')
            ->setPassword($this->userPassword->hashPassword($user, 'mdp123'))
            ->setPrenom('fntest')
            ->setRoles(['ROLE_ADMIN'])
            ->setDateEmbauche(new \DateTime())
            ->setDerniereConnexion(new \DateTime());
        $users[] = $userAdminTestFixe;
        $manager->persist($userAdminTestFixe);

        $userNOAdminTestFixe = new Collaborateur();
        $userNOAdminTestFixe
            -> setNom('lntest')
            ->setEmail('testnoadmin@test.fr')
            ->setPassword($this->userPassword->hashPassword($user, 'mdp123'))
            ->setPrenom('fntest')
            ->setDateEmbauche(new \DateTime())
            ->setDerniereConnexion(new \DateTime());
        $users[] = $userNOAdminTestFixe;
        $manager->persist($userNOAdminTestFixe);


        $restaurant = new Restaurant();
        $restaurant
            ->setNom('Au palais gourmand_t')
            ->setAdresse('3 rue des fourchette_t')
            ->setCodePostal('38080')
            ->setVille('Roche sur pain_t');
        $manager->persist($restaurant);

        $restaurant2 = new Restaurant();
        $restaurant2
            ->setNom('Au bouillon_test')
            ->setAdresse('adress_test')
            ->setCodePostal('99000')
            ->setVille('Roche_sur_test');
        $manager->persist($restaurant2);

        $functionEquipier = new Fonction();
        $functionEquipier
            ->setNom('Equipier_t');
        $manager->persist($functionEquipier);

        $functionManager = new Fonction();
        $functionManager
            ->setNom('Manager_t');
        $manager->persist($functionManager);

        for($i = 0; $i < 5 ; $i++) {

            $affectation = new Affectation();
            $affectation
                ->setCollaborateur($users[$i])
                ->setFonction($functionEquipier)
                ->setRestaurant($restaurant);
            $manager->persist($affectation);

        }



        $manager->flush();
    }
}
