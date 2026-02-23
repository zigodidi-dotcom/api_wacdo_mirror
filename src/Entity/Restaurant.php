<?php

namespace App\Entity;

use App\Repository\RestaurantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
class Restaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['restaurant:getAll', 'restaurant:detail', 'collaborateur:detail', 'collaborateur:list','affectation:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Un nom de restaurant est obligatoire')]
    #[Groups(['restaurant:getAll','restaurant:detail', 'collaborateur:detail', 'collaborateur:list','affectation:list'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Une adresse de restaurant est obligatoire')]
    #[Groups(['restaurant:getAll', 'restaurant:detail', 'collaborateur:detail'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Un code postal de restaurant est obligatoire')]
    #[Groups(['restaurant:getAll', 'restaurant:detail', 'collaborateur:detail'])]
    private ?int $code_postal = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Une ville pour le restaurant est obligatoire')]
    #[Groups(['restaurant:getAll', 'restaurant:detail', 'collaborateur:detail','affectation:list'])]
    private ?string $ville = null;

    /**
     * @var Collection<int, Affectation>
     */
    #[ORM\OneToMany(targetEntity: Affectation::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[Groups(['restaurant:detail'])]
    private Collection $affectations;

    public function __construct()
    {
        $this->affectations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): ?int
    {
        return $this->code_postal;
    }

    public function setCodePostal(int $code_postal): static
    {
        $this->code_postal = $code_postal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    /**
     * @return Collection<int, Affectation>
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function addAffectation(Affectation $affectation): static
    {
        if (!$this->affectations->contains($affectation)) {
            $this->affectations->add($affectation);
            $affectation->setRestaurant($this);
        }

        return $this;
    }

    public function removeAffectation(Affectation $affectation): static
    {
        if ($this->affectations->removeElement($affectation)) {
            // set the owning side to null (unless already changed)
            if ($affectation->getRestaurant() === $this) {
                $affectation->setRestaurant(null);
            }
        }

        return $this;
    }
}
