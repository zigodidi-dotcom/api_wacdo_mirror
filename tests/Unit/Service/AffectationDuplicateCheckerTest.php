<?php

namespace App\Tests\Unit\Service;

use App\Entity\Affectation;
use App\Entity\Collaborateur;
use App\Entity\Fonction;
use App\Entity\Restaurant;
use App\Service\AffectationDuplicateChecker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class AffectationDuplicateCheckerTest extends TestCase
{

    protected function setUp(): void
    {

        $this->restaurant = new Restaurant();
        $this->collaborateur = new Collaborateur();
        $this->fonction = new Fonction();

        $this->affectation = $this->createMock(Affectation::class);
        $this->affectation->method('getRestaurant')->willReturn($this->restaurant);
        $this->affectation->method('getCollaborateur')->willReturn($this->collaborateur);
        $this->affectation->method('getFonction')->willReturn($this->fonction);


        $this->repo = $this->createMock(EntityRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em
            ->expects(self::once())
            ->method('getRepository')
            ->with(Affectation::class)
            ->willReturn($this->repo);

        $this->checker = new AffectationDuplicateChecker($this->em);

    }

    public function testIsDuplicateReturnsFalseWhenNoExistingAffectation(): void
    {
        $this->repo
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'restaurant' => $this->restaurant,
                'collaborateur' => $this->collaborateur,
                'fonction' => $this->fonction,
            ])
            ->willReturn(null);

        self::assertFalse($this->checker->isDuplicate($this->affectation));
    }

    public function testIsDuplicateReturnsTrueWhenExistingAffectationFound(): void
    {

        $this->repo
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'restaurant' => $this->restaurant,
                'collaborateur' => $this->collaborateur,
                'fonction' => $this->fonction,
            ])
            ->willReturn($this->affectation);


        self::assertTrue($this->checker->isDuplicate($this->affectation));
    }
}
