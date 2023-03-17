<?php
namespace Magendoo\ProductWebhook\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const XML_PATH_ENDPOINT = 'magendoo_productwebhook/general/endpoint';
    const XML_PATH_USE_QUEUE = 'magendoo_productwebhook/general/use_queue';

    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getEndpoint($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENDPOINT, ScopeInterface::SCOPE_STORE, $storeId);
    }
    
    public function isUsingQueue($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_USE_QUEUE, ScopeInterface::SCOPE_STORE, $storeId);
    }

}
