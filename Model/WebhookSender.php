<?php
declare(strict_types=1);

namespace Magendoo\ProductWebhook\Model;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Handles sending webhook HTTP requests with proper error handling
 */
class WebhookSender
{
    /**
     * HTTP success status codes
     */
    private const SUCCESS_STATUS_CODES = [200, 201, 202, 204];

    /**
     * @var Curl
     */
    private Curl $curlClient;

    /**
     * @var Json
     */
    private Json $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Curl $curlClient
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(
        Curl $curlClient,
        Json $jsonSerializer,
        LoggerInterface $logger,
        Config $config
    ) {
        $this->curlClient = $curlClient;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Send product data to webhook endpoint
     *
     * @param array $productData
     * @param int|null $storeId
     * @return bool
     */
    public function send(array $productData, ?int $storeId = null): bool
    {
        $endpoint = $this->config->getEndpoint($storeId);

        if (empty($endpoint)) {
            $this->logger->warning('Webhook endpoint not configured', [
                'store_id' => $storeId
            ]);
            return false;
        }

        try {
            $productJson = $this->jsonSerializer->serialize($productData);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Failed to serialize product data for webhook', [
                'product_id' => $productData['entity_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }

        try {
            $timeout = $this->config->getTimeout($storeId);

            // Configure cURL
            $this->curlClient->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curlClient->setOption(CURLOPT_TIMEOUT, $timeout);
            $this->curlClient->setOption(CURLOPT_CONNECTTIMEOUT, min($timeout, 5));
            $this->curlClient->setOption(CURLOPT_FOLLOWLOCATION, false); // Security: don't follow redirects
            $this->curlClient->setOption(CURLOPT_MAXREDIRS, 0);
            $this->curlClient->addHeader('Content-Type', 'application/json');
            $this->curlClient->addHeader('User-Agent', 'Magento-ProductWebhook/1.0');

            // Make request
            $this->curlClient->post($endpoint, $productJson);

            // Get response
            $statusCode = $this->curlClient->getStatus();
            $responseBody = $this->curlClient->getBody();

            // Validate response
            if (!in_array($statusCode, self::SUCCESS_STATUS_CODES)) {
                $this->logger->error('Webhook request failed with non-success status', [
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                    'product_id' => $productData['entity_id'] ?? 'unknown'
                ]);
                return false;
            }

            $this->logger->info('Webhook sent successfully', [
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'product_id' => $productData['entity_id'] ?? 'unknown'
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->critical('Exception during webhook request', [
                'endpoint' => $endpoint,
                'product_id' => $productData['entity_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
