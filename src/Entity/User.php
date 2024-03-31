<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 4)]
    private ?string $firstYear = null;

    #[ORM\OneToMany(targetEntity: Reading::class, mappedBy: 'user')]
    private Collection $reading;

    public function __construct()
    {
        $this->reading = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstYear(): ?string
    {
        return $this->firstYear;
    }

    public function setFirstYear(string $firstYear): static
    {
        $this->firstYear = $firstYear;

        return $this;
    }

    /**
     * @return Collection<int, Reading>
     */
    public function getReadingCollection(): Collection
    {
        return $this->reading;
    }

    public function addReading(Reading $reading): static
    {
        if (!$this->reading->contains($reading)) {
            $this->reading->add($reading);
            $reading->setUser($this);
        }

        return $this;
    }

    public function removeReading(Reading $reading): static
    {
        if ($this->reading->removeElement($reading)) {
            // set the owning side to null (unless already changed)
            if ($reading->getUser() === $this) {
                $reading->setUser(null);
            }
        }

        return $this;
    }
}
