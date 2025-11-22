<?php
declare(strict_types=1);

namespace Magendoo\ProductWebhook\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magendoo\ProductWebhook\Model\Config;
use Magendoo\ProductWebhook\Model\DataFilter;
use Magendoo\ProductWebhook\Model\WebhookSender;
use Psr\Log\LoggerInterface;

/**
 * Observer for catalog_product_save_after event
 * Sends product data to webhook or queue
 */
class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

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
     * @param PublisherInterface $publisher
     * @param WebhookSender $webhookSender
     * @param DataFilter $dataFilter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        PublisherInterface $publisher,
        WebhookSender $webhookSender,
        DataFilter $dataFilter,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->publisher = $publisher;
        $this->webhookSender = $webhookSender;
        $this->dataFilter = $dataFilter;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getProduct();

        if (!$product || !$product->getId()) {
            $this->logger->warning('Product save observer triggered without valid product');
            return;
        }

        $storeId = (int) $product->getStoreId();

        if (!$this->config->isEnabled($storeId)) {
            $this->logger->debug('Webhook disabled for store', ['store_id' => $storeId]);
            return;
        }

        try {
            // Filter sensitive data
            $productData = $this->dataFilter->filterProductData($product->getData());

            if ($this->config->isUsingQueue($storeId)) {
                $this->logger->info('Publishing product data to queue', [
                    'product_id' => $product->getId(),
                    'store_id' => $storeId
                ]);

                $this->publisher->publish('magendoo.productdata.created', $productData);
            } else {
                $this->logger->info('Sending product data to webhook directly', [
                    'product_id' => $product->getId(),
                    'store_id' => $storeId
                ]);

                $this->webhookSender->send($productData, $storeId);
            }
        } catch (\Exception $e) {
            // Log but don't fail product save
            $this->logger->critical('Failed to process product webhook', [
                'product_id' => $product->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
