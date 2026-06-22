<?php

namespace App\Entity;

use App\Enum\ScheduleEventType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'schedule_event')]
#[ORM\UniqueConstraint(name: 'uniq_schedule_external_key', columns: ['external_key'])]
#[ORM\Index(name: 'idx_schedule_product_starts', columns: ['product_id', 'starts_at'])]
class ScheduleEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: ScheduleVenue::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ScheduleVenue $venue;

    #[ORM\Column]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column]
    private \DateTimeImmutable $endsAt;

    #[ORM\Column(length: 512)]
    private string $title = '';

    #[ORM\Column(length: 16, enumType: ScheduleEventType::class)]
    private ScheduleEventType $eventType = ScheduleEventType::Program;

    #[ORM\Column(length: 64)]
    private string $externalKey = '';

    #[ORM\Column]
    private bool $isPublished = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getVenue(): ScheduleVenue
    {
        return $this->venue;
    }

    public function setVenue(ScheduleVenue $venue): static
    {
        $this->venue = $venue;

        return $this;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getEventType(): ScheduleEventType
    {
        return $this->eventType;
    }

    public function setEventType(ScheduleEventType $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getExternalKey(): string
    {
        return $this->externalKey;
    }

    public function setExternalKey(string $externalKey): static
    {
        $this->externalKey = $externalKey;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }
}
