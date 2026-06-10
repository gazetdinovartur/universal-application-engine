<?php

namespace App\Command;

use App\Entity\ParticipationOption;
use App\Entity\ParticipationPrice;
use App\Entity\PricingPeriod;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:hanuman-fest',
    description: 'Seed Hanuman Fest product, pricing periods and participation options',
)]
class SeedHanumanFestCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['slug' => 'hanuman-fest-2027']);
        if ($product) {
            $io->warning('Product hanuman-fest-2027 already exists, skipping seed.');

            return Command::SUCCESS;
        }

        $product = new Product();
        $product->setName('Hanuman Fest 2027');
        $product->setSlug('hanuman-fest-2027');
        $product->setIsActive(true);
        $this->entityManager->persist($product);

        $periods = [
            ['Early Bird', '2026-01-01', '2027-03-01', 8200],
            ['Regular', '2027-03-01', '2027-04-01', 9200],
            ['Last Chance', '2027-04-01', '2027-07-01', 12000],
        ];

        $optionDefs = [
            ['OWN_HOUSE_NO_FOOD', 'Свой дом, без питания'],
            ['OWN_HOUSE_FOOD', 'Свой дом, с питанием'],
            ['OUR_TENT_NO_FOOD', 'Наш шатёр, без питания'],
            ['OUR_TENT_FOOD', 'Наш шатёр, с питанием'],
            ['ONE_DAY', 'Один день'],
            ['ONE_DAY_FOOD', 'Один день с питанием'],
        ];

        $options = [];
        foreach ($optionDefs as [$code, $name]) {
            $option = new ParticipationOption();
            $option->setProduct($product);
            $option->setCode($code);
            $option->setName($name);
            $this->entityManager->persist($option);
            $options[$code] = $option;
        }

        foreach ($periods as [$name, $start, $end, $basePrice]) {
            $period = new PricingPeriod();
            $period->setProduct($product);
            $period->setName($name);
            $period->setStartAt(new \DateTimeImmutable($start));
            $period->setEndAt(new \DateTimeImmutable($end));
            $period->setIsActive(true);
            $this->entityManager->persist($period);

            foreach ($options as $code => $option) {
                $price = new ParticipationPrice();
                $price->setPricingPeriod($period);
                $price->setParticipationOption($option);
                $price->setPrice(match ($code) {
                    'ONE_DAY' => (int) round($basePrice * 0.35),
                    'ONE_DAY_FOOD' => (int) round($basePrice * 0.45),
                    'OUR_TENT_NO_FOOD', 'OUR_TENT_FOOD' => (int) round($basePrice * 0.85),
                    default => $basePrice,
                });
                $this->entityManager->persist($price);
            }
        }

        $this->entityManager->flush();

        $io->success('Hanuman Fest 2027 seed data created.');

        return Command::SUCCESS;
    }
}
