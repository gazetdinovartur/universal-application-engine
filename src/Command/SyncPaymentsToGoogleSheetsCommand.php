<?php

namespace App\Command;

use App\Service\PaymentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Аналог WP REST: GET /wp-json/yk/v1/sync
 */
#[AsCommand(
    name: 'app:payments:sync-google-sheets',
    description: 'Re-export all succeeded payments to Google Sheets',
)]
class SyncPaymentsToGoogleSheetsCommand extends Command
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->paymentService->syncSucceededPaymentsToGoogleSheets();
        $io->success(sprintf('Exported %d payment(s) to Google Sheets.', $count));

        return Command::SUCCESS;
    }
}
