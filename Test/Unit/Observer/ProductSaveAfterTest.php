<?php

namespace Magendoo\ProductWebhook\Test\Unit\Observer;

use Magendoo\ProductWebhook\Observer\ProductSaveAfter;
use Magendoo\ProductWebhook\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Product;

use PHPUnit\Framework\TestCase;

class ProductSaveAfterTest extends TestCase
{
    private $config;
    private $jsonSerializer;
    private $curlClient;
    private $observer;
    private $publisher;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->jsonSerializer = $this->createMock(Json::class);
        $this->curlClient = $this->createMock(Curl::class);
        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->observer = new ProductSaveAfter(
            $this->config,
            $this->jsonSerializer,
            $this->curlClient,
            $this->publisher
        );
    }

    public function testExecute()
    {
        $storeId = 1;
        $productData = ['id' => 1, 'name' => 'Test Product'];
        $productJson = '{"id":1,"name":"Test Product"}';
        $endpoint = 'https://example.com/api/products';

        $product = $this->createMock(Product::class);
        $product->method('getStoreId')->willReturn($storeId);
        $product->method('getData')->willReturn($productData);
        $product->method('getStoreId')->willReturn(1);
        
        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getData']);
        $event->method('getData')->with('product')->willReturn($product);

        $observer = $this->getMockBuilder(Observer::class)
                         ->disableOriginalConstructor()
                         ->getMock();
        $observer->method('getEvent')->willReturn($event);
        

        $this->config->method('getEndpoint')->with($storeId)->willReturn($endpoint);
        $this->config->method('isUsingQueue')->with($storeId)->willReturn(false);

        $this->jsonSerializer->expects($this->once())
                              ->method('serialize')
                              ->with($productData)
                              ->willReturn($productJson);

        $this->curlClient->expects($this->once())
                         ->method('post')
                         ->with($endpoint, $productJson);

        $this->observer->execute($observer);
    }
}
