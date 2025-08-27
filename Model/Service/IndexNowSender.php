<?php
declare(strict_types=1);

namespace WebAtypique\IndexNow\Model\Service;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use WebAtypique\IndexNow\Helper\Data as ConfigHelper;

class IndexNowSender
{
    public function __construct(
        private Curl $curl,
        private ConfigHelper $configHelper,
        private LoggerInterface $logger
    ) {}

    public function submitUrl(string $url): bool
    {
        if (!$this->configHelper->isEnabled()) {
            $this->logger->info('[IndexNow] Module disabled. Skipping URL submission.');
            return false;
        }

        $apiKey = $this->configHelper->getApiKey();
        $endpoint = rtrim((string)$this->configHelper->getEndpointUrl(), '/');

        if (empty($apiKey) || empty($endpoint)) {
            $this->logger->error('[IndexNow] Missing API Key or Endpoint URL.');
            return false;
        }

        // Prépare le payload avec keyLocation si défini
        $payload = [
            'host' => parse_url($url, PHP_URL_HOST),
            'key' => $apiKey,
            'urlList' => [$url]
        ];

        $keyLocation = $this->configHelper->getKeyLocation();
        if (!empty($keyLocation)) {
            $payload['keyLocation'] = $keyLocation;
        }

        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($endpoint, json_encode($payload));

            $statusCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info("[IndexNow] URL submitted successfully: {$url}");
                return true;
            }

            $this->logger->error(
                "[IndexNow] Failed to submit URL: {$url}. Status: {$statusCode}. Response: {$responseBody}"
            );
            return false;

        } catch (\Throwable $e) {
            $this->logger->error("[IndexNow] Exception during URL submission: {$e->getMessage()}");
            return false;
        }
    }
}
