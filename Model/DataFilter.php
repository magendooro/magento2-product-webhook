<?php
declare(strict_types=1);

namespace Magendoo\ProductWebhook\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Filters product data to remove sensitive attributes
 */
class DataFilter
{
    /**
     * Sensitive attributes that should never be sent
     */
    private const SENSITIVE_ATTRIBUTES = [
        'cost',
        'password',
        'tax_class_id',
        'tier_price',
        'media_gallery',
        'custom_layout_update',
        'custom_design',
        'page_layout',
        'options_container',
        'country_of_manufacture'
    ];

    /**
     * System/internal attributes
     */
    private const SYSTEM_ATTRIBUTES = [
        'entity_id',
        'attribute_set_id',
        'created_at',
        'updated_at',
        'has_options',
        'required_options',
        'is_recurring',
        'recurring_profile'
    ];

    private const XML_PATH_ALLOWED_ATTRIBUTES = 'magendoo_productwebhook/general/allowed_attributes';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Filter product data to include only safe attributes
     *
     * @param array $productData
     * @param int|null $storeId
     * @return array
     */
    public function filterProductData(array $productData, ?int $storeId = null): array
    {
        $allowedAttributes = $this->getAllowedAttributes($storeId);

        // If specific attributes configured, use only those
        if (!empty($allowedAttributes)) {
            $filteredData = [];
            foreach ($allowedAttributes as $attribute) {
                if (isset($productData[$attribute])) {
                    $filteredData[$attribute] = $productData[$attribute];
                }
            }

            $this->logger->debug('Filtered product data using whitelist', [
                'allowed_count' => count($allowedAttributes),
                'sent_count' => count($filteredData)
            ]);

            return $filteredData;
        }

        // Otherwise, remove sensitive and system attributes
        $filteredData = $productData;

        foreach (self::SENSITIVE_ATTRIBUTES as $attribute) {
            if (isset($filteredData[$attribute])) {
                unset($filteredData[$attribute]);
            }
        }

        foreach (self::SYSTEM_ATTRIBUTES as $attribute) {
            if (isset($filteredData[$attribute])) {
                unset($filteredData[$attribute]);
            }
        }

        $this->logger->debug('Filtered product data using blacklist', [
            'original_count' => count($productData),
            'filtered_count' => count($filteredData),
            'removed' => count($productData) - count($filteredData)
        ]);

        return $filteredData;
    }

    /**
     * Get allowed attributes from configuration
     *
     * @param int|null $storeId
     * @return array
     */
    private function getAllowedAttributes(?int $storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($value)) {
            return [];
        }

        // Parse comma-separated list
        $attributes = array_map('trim', explode(',', $value));
        return array_filter($attributes);
    }
}
