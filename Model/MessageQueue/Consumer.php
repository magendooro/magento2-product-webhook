<?php
declare(strict_types=1);

namespace Magendoo\ProductWebhook\Model\MessageQueue;

use Magendoo\ProductWebhook\Model\Config;
use Magendoo\ProductWebhook\Model\DataFilter;
use Magendoo\ProductWebhook\Model\WebhookSender;
use Psr\Log\LoggerInterface;

/**
 * Message queue consumer for product webhook
 */
class Consumer
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var WebhookSender
     */
    private WebhookSender $webhookSender;

    /**
     * @var DataFilter
     */
    private DataFilter $dataFilter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Config $config
     * @param WebhookSender $webhookSender
     * @param DataFilter $dataFilter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        WebhookSender $webhookSender,
        DataFilter $dataFilter,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->webhookSender = $webhookSender;
        $this->dataFilter = $dataFilter;
        $this->logger = $logger;
    }

    /**
     * Process message from queue
     *
     * @param array $productData
     * @return void
     */
    public function processMessage(array $productData): void
    {
        if (empty($productData)) {
            $this->logger->warning('Empty product data received in queue consumer');
            return;
        }

        $storeId = isset($productData['store_id']) ? (int) $productData['store_id'] : null;

        if (!$this->config->isEnabled($storeId)) {
            $this->logger->debug('Webhook disabled, skipping queue message', [
                'store_id' => $storeId,
                'product_id' => $productData['entity_id'] ?? 'unknown'
            ]);
            return;
        }

        try {
            // Filter data before sending
            $filteredData = $this->dataFilter->filterProductData($productData, $storeId);

            $this->logger->info('Processing webhook from queue', [
                'product_id' => $productData['entity_id'] ?? 'unknown',
                'store_id' => $storeId
            ]);

            $result = $this->webhookSender->send($filteredData, $storeId);

            if (!$result) {
                $this->logger->warning('Webhook send failed for queued message', [
                    'product_id' => $productData['entity_id'] ?? 'unknown',
                    'store_id' => $storeId
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->critical('Exception processing webhook queue message', [
                'product_id' => $productData['entity_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't re-throw - let message be acknowledged to prevent infinite retries
        }
    }
}
