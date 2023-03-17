<?php
namespace Magendoo\ProductWebhook\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magendoo\ProductWebhook\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class ProductSaveAfter
 * @package Magendoo\ProductWebhook\Observer
 */
class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Curl
     */
    private $curlClient;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * constructor
     *
     * @param Config $config
     * @param Json $jsonSerializer
     * @param Curl $curlClient
     */
    public function __construct(
        Config $config,
        Json $jsonSerializer,
        Curl $curlClient,
        PublisherInterface $publisher

    ) {
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->curlClient = $curlClient;
        $this->publisher = $publisher;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $storeId = $product->getStoreId();
        $endpoint = $this->config->getEndpoint($storeId);

        $product = $observer->getEvent()->getProduct();
        $storeId = $product->getStoreId();
        $productData = $product->getData();

        if ($this->config->isUsingQueue($storeId)) {            
            $this->publisher->publish('magendoo.productdata.created', $productData);
        } else {        
            if ($endpoint) {                
                $productJson = $this->jsonSerializer->serialize($productData);
                $this->curlClient->setOption(CURLOPT_RETURNTRANSFER, true);
                $this->curlClient->setOption(CURLOPT_TIMEOUT, 5);
                $this->curlClient->setOption(CURLOPT_CONNECTTIMEOUT, 5);
                $this->curlClient->addHeader('Content-Type', 'application/json');
                $this->curlClient->post($endpoint, $productJson);
            }
        }
    }
}
