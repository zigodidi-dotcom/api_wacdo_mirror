<?php

namespace App\Entity;

use App\Repository\CollaborateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollaborateurRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class Collaborateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['collaborateur:list', 'collaborateur:detail','restaurant:detail','affectation:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "l'email est obligatoire")]
    #[Assert\Email(message:'L email {{ value }} ne respect pas un format valide')]
    #[Groups(['collaborateur:list', 'collaborateur:detail','restaurant:detail', 'collaborateur:create'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['collaborateur:list', 'collaborateur:detail','restaurant:detail', 'collaborateur:create'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\PasswordStrength(message: "Le mot de passe choisis est trop faible. il doit être plus long et/ou avec plus de caractères uniques")]
    #[Assert\NotBlank(message: "le mot de passe est obligatoire")]
    #[Groups(['collaborateur:create'])]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['collaborateur:list', 'collaborateur:detail','restaurant:detail','affectation:list','collaborateur:create'])]
    #[Assert\NotBlank(message: "le prenom est obligatoire")]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['collaborateur:list', 'collaborateur:detail','restaurant:detail','affectation:list','collaborateur:create'])]
    #[Assert\NotBlank(message: "le nom est obligatoire")]
    private ?string $nom = null;


    #[ORM\Column(nullable: true)]
    #[Groups(['collaborateur:list', 'collaborateur:detail','restaurant:detail', 'collaborateur:create'])]
    private ?\DateTime $dateembauche = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['collaborateur:list', 'collaborateur:detail','restaurant:detail'])]
    private ?\DateTime $derniere_connexion = null;

    /**
     * @var Collection<int, Affectation>
     */
    #[ORM\OneToMany(targetEntity: Affectation::class, mappedBy: 'collaborateur', orphanRemoval: true)]
    #[Groups(['collaborateur:detail', 'collaborateur:list'])]
    private Collection $affectations;

    public function __construct()
    {
        $this->affectations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getId(); // ou $this->getEmail()
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
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


    public function getDateembauche(): ?\DateTime
    {
        return $this->dateembauche;
    }

    public function setDateembauche(?\DateTime $dateembauche): static
    {
        $this->dateembauche = $dateembauche;

        return $this;
    }

    public function getDerniereConnexion(): ?\DateTime
    {
        return $this->derniere_connexion;
    }

    public function setDerniereConnexion(?\DateTime $derniere_connexion): static
    {
        $this->derniere_connexion = $derniere_connexion;

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
            $affectation->setCollaborateur($this);
        }

        return $this;
    }

    public function removeAffectation(Affectation $affectation): static
    {
        if ($this->affectations->removeElement($affectation)) {
            // set the owning side to null (unless already changed)
            if ($affectation->getCollaborateur() === $this) {
                $affectation->setCollaborateur(null);
            }
        }

        return $this;
    }
}
