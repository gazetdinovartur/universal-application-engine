<?php

namespace App\Command;

use App\Entity\Application;
use App\Enum\ApplicationStatus;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:applications:recalculate-statuses',
    description: 'Recalculate paid amounts/statuses based on succeeded payments',
)]
class RecalculateApplicationStatusesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('product-slug', null, InputOption::VALUE_OPTIONAL, 'Limit to one product slug')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview updates without writing to DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $productSlug = (string) $input->getOption('product-slug');

        $qb = $this->entityManager->getRepository(Application::class)->createQueryBuilder('a')
            ->leftJoin('a.payments', 'pay')
            ->addSelect('pay');

        if ($productSlug !== '') {
            $qb
                ->leftJoin('a.product', 'p')
                ->andWhere('p.slug = :slug')
                ->setParameter('slug', $productSlug);
        }

        /** @var list<Application> $applications */
        $applications = $qb->getQuery()->getResult();
        if ($applications === []) {
            $io->success('No applications found for recalculation.');

            return Command::SUCCESS;
        }

        $changed = 0;
        foreach ($applications as $application) {
            $succeededTotal = 0;
            foreach ($application->getPayments() as $payment) {
                if ($payment->getStatus() === PaymentStatus::Succeeded) {
                    $succeededTotal += max(0, $payment->getAmount());
                }
            }

            $currentPaid = max(0, $application->getPaidAmount());
            $newPaid = max($currentPaid, $succeededTotal);
            $total = max(0, $application->getTotalAmount());
            $newStatus = $this->resolveStatus($newPaid, $total);

            $isChanged = $newPaid !== $application->getPaidAmount() || $newStatus !== $application->getStatus();
            if (!$isChanged) {
                continue;
            }

            ++$changed;
            if ($dryRun) {
                continue;
            }

            $application->setPaidAmount($newPaid);
            $application->setStatus($newStatus);
        }

        if ($dryRun) {
            $io->success(sprintf('Dry-run: applications_to_update=%d (of %d)', $changed, count($applications)));

            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success(sprintf('Done: updated=%d, scanned=%d', $changed, count($applications)));

        return Command::SUCCESS;
    }

    private function resolveStatus(int $paidAmount, int $totalAmount): ApplicationStatus
    {
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            return ApplicationStatus::Paid;
        }

        if ($paidAmount > 0) {
            return ApplicationStatus::PartiallyPaid;
        }

        return ApplicationStatus::New;
    }
}
