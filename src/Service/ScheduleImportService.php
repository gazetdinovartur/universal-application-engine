<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ScheduleEvent;
use App\Entity\ScheduleImport;
use App\Entity\ScheduleVenue;
use App\Infrastructure\Schedule\Dto\ParsedSchedule;
use App\Infrastructure\Schedule\Dto\ParsedScheduleEvent;
use App\Infrastructure\Schedule\ScheduleMatrixParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ScheduleImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScheduleMatrixParser $parser,
        private readonly SluggerInterface $slugger,
    ) {
    }

    /**
     * @return array{events: int, venues: int, skipped: bool}
     */
    public function importFromCsv(Product $product, string $csvContent, ?string $sourceUrl = null, bool $dryRun = false, bool $force = false): array
    {
        $sourceHash = hash('sha256', $csvContent);
        $latestImport = $this->findLatestImport($product);

        if (!$force && $latestImport !== null && $latestImport->getSourceHash() === $sourceHash) {
            return [
                'events' => $latestImport->getEventCount(),
                'venues' => $latestImport->getVenueCount(),
                'skipped' => true,
            ];
        }

        $parsed = $this->parser->parse($csvContent);

        if ($dryRun) {
            return [
                'events' => count($parsed->events),
                'venues' => count($parsed->venueNames),
                'skipped' => false,
            ];
        }

        $this->entityManager->wrapInTransaction(function () use ($product, $parsed, $sourceHash, $sourceUrl): void {
            $this->replaceSchedule($product, $parsed);

            $import = (new ScheduleImport())
                ->setProduct($product)
                ->setImportedAt(new \DateTimeImmutable())
                ->setSourceHash($sourceHash)
                ->setEventCount(count($parsed->events))
                ->setVenueCount(count($parsed->venueNames))
                ->setSourceUrl($sourceUrl);

            $this->entityManager->persist($import);
        });

        return [
            'events' => count($parsed->events),
            'venues' => count($parsed->venueNames),
            'skipped' => false,
        ];
    }

    private function replaceSchedule(Product $product, ParsedSchedule $parsed): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\ScheduleEvent e WHERE e.product = :product')
            ->setParameter('product', $product)
            ->execute();

        $this->entityManager->createQuery('DELETE FROM App\Entity\ScheduleVenue v WHERE v.product = :product')
            ->setParameter('product', $product)
            ->execute();

        /** @var array<string, ScheduleVenue> $venueBySlug */
        $venueBySlug = [];
        foreach ($parsed->venueNames as $index => $venueName) {
            $slug = $this->buildVenueSlug($venueName);
            $venue = (new ScheduleVenue())
                ->setProduct($product)
                ->setSlug($slug)
                ->setName($venueName)
                ->setSortOrder($index);

            $this->entityManager->persist($venue);
            $venueBySlug[$slug] = $venue;
        }

        foreach ($parsed->events as $parsedEvent) {
            $venueSlug = $this->buildVenueSlug($parsedEvent->venueName);
            $venue = $venueBySlug[$venueSlug] ?? null;
            if ($venue === null) {
                continue;
            }

            $event = (new ScheduleEvent())
                ->setProduct($product)
                ->setVenue($venue)
                ->setStartsAt($parsedEvent->startsAt)
                ->setEndsAt($parsedEvent->endsAt)
                ->setTitle($parsedEvent->title)
                ->setEventType($parsedEvent->eventType)
                ->setExternalKey($this->buildExternalKey($product, $parsedEvent, $venueSlug))
                ->setIsPublished($parsedEvent->eventType !== \App\Enum\ScheduleEventType::Hidden);

            $this->entityManager->persist($event);
        }
    }

    private function buildVenueSlug(string $venueName): string
    {
        $slug = strtolower((string) $this->slugger->slug($venueName)->toString());

        return $slug !== '' ? $slug : 'venue';
    }

    private function buildExternalKey(Product $product, ParsedScheduleEvent $event, string $venueSlug): string
    {
        return hash('sha256', implode('|', [
            $product->getSlug(),
            $venueSlug,
            $event->startsAt->format(\DateTimeInterface::ATOM),
            $event->title,
        ]));
    }

    private function findLatestImport(Product $product): ?ScheduleImport
    {
        return $this->entityManager->getRepository(ScheduleImport::class)->findOneBy(
            ['product' => $product],
            ['importedAt' => 'DESC'],
        );
    }
}
