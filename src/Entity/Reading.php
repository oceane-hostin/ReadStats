<?php

namespace App\Entity;

use App\Repository\ReadingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReadingRepository::class)]
class Reading
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 4)]
    private ?string $year = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $month = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: "boolean")]
    private bool $isOwned = false;

    #[ORM\Column(type: "boolean")]
    private bool $isBorrowed = false;

    #[ORM\ManyToOne]
    private ?Book $book = null;

    #[ORM\ManyToOne(inversedBy: 'reading')]
    private ?User $user = null;

    #[ORM\Column(type: "boolean")]
    private bool $isEbook = false;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(string $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }


    public function getIsOwned(): bool
    {
        return $this->isOwned;
    }

    public function setIsOwned(bool $isOwned): static
    {
        $this->isOwned = $isOwned;

        return $this;
    }

    public function getIsBorrowed(): bool
    {
        return $this->isBorrowed;
    }

    public function setIsBorrowed(bool $isBorrowed): static
    {
        $this->isBorrowed = $isBorrowed;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isIsEbook(): bool
    {
        return $this->isEbook;
    }

    public function setIsEbook(bool $isEbook): static
    {
        $this->isEbook = $isEbook;

        return $this;
    }
}
