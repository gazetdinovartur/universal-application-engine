<?php

namespace App\Infrastructure\GoogleSheets;

use App\Infrastructure\GoogleSheets\Dto\PaymentExportPayload;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Портировано из legacy/google-proxy.php — прокси POST → Google Apps Script.
 */
class GoogleSheetsClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $webhookUrl,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function exportPayment(PaymentExportPayload $payload): void
    {
        if ($this->webhookUrl === '') {
            $this->logger?->warning('Google Sheets webhook URL is not configured, export skipped');

            return;
        }

        $response = $this->httpClient->request('POST', $this->webhookUrl, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload->toArray(),
            'max_redirects' => 5,
            'timeout' => 20,
        ]);

        $this->logger?->info('Google Sheets export response', [
            'status' => $response->getStatusCode(),
            'body' => $response->getContent(false),
        ]);
    }
}
