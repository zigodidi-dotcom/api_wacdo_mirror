<?php

// src/Service/AffectationDuplicateChecker.php
namespace App\Service;

use App\Entity\Affectation;
use Doctrine\ORM\EntityManagerInterface;

class AffectationDuplicateChecker
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function isDuplicate(Affectation $affectation): bool
    {
        $existing = $this->em->getRepository(Affectation::class)->findOneBy([
            'restaurant' => $affectation->getRestaurant(),
            'collaborateur' => $affectation->getCollaborateur(),
        ]);

        return $existing !== null;
    }
}
