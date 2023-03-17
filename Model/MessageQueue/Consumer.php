<?php

namespace Magendoo\ProductWebhook\Model\MessageQueue;

use Magendoo\ProductWebhook\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\HTTP\Client\Curl;

class Consumer
{
    private $config;
    private $jsonSerializer;
    private $curlClient;

    public function __construct(
        Config $config,
        Json $jsonSerializer,
        Curl $curlClient
    ) {
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->curlClient = $curlClient;
    }

    public function processMessage($productData)
    {
        $storeId = $productData['store_id'];
        $endpoint = $this->config->getEndpoint($storeId);

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
