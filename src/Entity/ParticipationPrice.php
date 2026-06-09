<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'participation_price')]
#[ORM\UniqueConstraint(name: 'uniq_period_option', columns: ['pricing_period_id', 'participation_option_id'])]
class ParticipationPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participationPrices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PricingPeriod $pricingPeriod = null;

    #[ORM\ManyToOne(inversedBy: 'participationPrices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ParticipationOption $participationOption = null;

    #[ORM\Column]
    private int $price = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPricingPeriod(): ?PricingPeriod
    {
        return $this->pricingPeriod;
    }

    public function setPricingPeriod(?PricingPeriod $pricingPeriod): static
    {
        $this->pricingPeriod = $pricingPeriod;

        return $this;
    }

    public function getParticipationOption(): ?ParticipationOption
    {
        return $this->participationOption;
    }

    public function setParticipationOption(?ParticipationOption $participationOption): static
    {
        $this->participationOption = $participationOption;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s — %d ₽', $this->participationOption?->getName() ?? '', $this->price);
    }
}
