<?php
declare(strict_types=1);

namespace Magendoo\ProductWebhook\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Configuration provider for ProductWebhook module
 */
class Config
{
    private const XML_PATH_ENDPOINT = 'magendoo_productwebhook/general/endpoint';
    private const XML_PATH_USE_QUEUE = 'magendoo_productwebhook/general/use_queue';
    private const XML_PATH_TIMEOUT = 'magendoo_productwebhook/general/timeout';
    private const XML_PATH_ENABLED = 'magendoo_productwebhook/general/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var UrlValidator
     */
    private UrlValidator $urlValidator;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlValidator $urlValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlValidator $urlValidator,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlValidator = $urlValidator;
        $this->logger = $logger;
    }

    /**
     * Get validated webhook endpoint URL
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getEndpoint(?int $storeId = null): ?string
    {
        $endpoint = $this->scopeConfig->getValue(
            self::XML_PATH_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($endpoint)) {
            return null;
        }

        try {
            $this->urlValidator->validate($endpoint);
            return $endpoint;
        } catch (LocalizedException $e) {
            $this->logger->error('Invalid webhook endpoint configured', [
                'endpoint' => $endpoint,
                'store_id' => $storeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if queue processing is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isUsingQueue(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_QUEUE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get HTTP timeout in seconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getTimeout(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_TIMEOUT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 10;
    }

    /**
     * Check if webhook is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
