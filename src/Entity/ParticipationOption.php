<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'participation_option')]
#[ORM\UniqueConstraint(name: 'uniq_product_code', columns: ['product_id', 'code'])]
class ParticipationOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participationOptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(length: 64)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $name = '';

    /** @var Collection<int, ParticipationPrice> */
    #[ORM\OneToMany(targetEntity: ParticipationPrice::class, mappedBy: 'participationOption', orphanRemoval: true)]
    private Collection $participationPrices;

    public function __construct()
    {
        $this->participationPrices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** @return Collection<int, ParticipationPrice> */
    public function getParticipationPrices(): Collection
    {
        return $this->participationPrices;
    }

    public function addParticipationPrice(ParticipationPrice $participationPrice): static
    {
        if (!$this->participationPrices->contains($participationPrice)) {
            $this->participationPrices->add($participationPrice);
            $participationPrice->setParticipationOption($this);
        }

        return $this;
    }

    public function removeParticipationPrice(ParticipationPrice $participationPrice): static
    {
        if ($this->participationPrices->removeElement($participationPrice)) {
            if ($participationPrice->getParticipationOption() === $this) {
                $participationPrice->setParticipationOption(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
