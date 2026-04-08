<?php

namespace App\Entity;

use App\Repository\AffectationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: AffectationRepository::class)]
class Affectation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['restaurant:detail', 'collaborateur:list', 'collaborateur:detail', 'affectation:list'])]
    private ?int $id = null;

    #[ORM\ManyToOne(fetch:"EAGER", inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: "Un collaborateur est obligatoire")]
    #[Groups(['collaborateur:list', 'collaborateur:detail', 'affectation:list', 'affectation:create'])]
    private ?Restaurant $restaurant = null;

    #[ORM\ManyToOne(fetch:"EAGER", inversedBy: 'affectations')]
    #[Groups(['affectation:list','collaborateur:detail', 'affectation:create'])]
    #[Assert\NotBlank(message: "Une fonction est obligatoire")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fonction $fonction = null;

    #[ORM\ManyToOne(fetch:"EAGER", inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: "Un Restaurant est obligatoire")]
    #[Groups(['restaurant:detail','affectation:list', 'affectation:create'])]
    private ?Collaborateur $collaborateur = null;



    #[ORM\Column(type: 'boolean')]
    #[Groups(['affectation:list', 'affectation:create', 'collaborateur:detail'])]
    private ?bool $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;

        return $this;
    }

    public function getFonction(): ?Fonction
    {
        return $this->fonction;
    }

    public function setFonction(?Fonction $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getCollaborateur(): ?Collaborateur
    {
        return $this->collaborateur;
    }

    public function setCollaborateur(?Collaborateur $collaborateur): static
    {
        $this->collaborateur = $collaborateur;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

}
