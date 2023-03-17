# Magendoo Product Webhook

The Magendoo_ProductWebhook module is a Magento 2 extension that sends product data to a third-party endpoint when a product is created, either via the admin interface or the REST API. The module allows you to configure the third-party endpoint through the admin panel, and it also provides an option to send product data to a message queue (RabbitMQ) before sending it to the third-party endpoint.

It is useful for scenarios that involve additional data enriching workflows for your product catalog.

## Installation

To install the **Magendoo_ProductWebhook** module via Composer, follow these steps:

1. If the module is hosted on GitHub, add the repository to your Magento 2 project's `composer.json` file:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/magendooro/magento2-product-webhook.git"
    }
]
```
2. Run the following command to require the module:
```
composer require magendoo/product-webhook
```

3. Enable the module and run setup upgrade:

```
bin/magento module:enable Magendoo_ProductWebhook
bin/magento setup:upgrade
bin/magento cache:clean
```
4. If you want to use the message queue functionality, make sure to configure RabbitMQ with your Magento instance. Follow the official documentation for setting up RabbitMQ with Magento:

    [Configure message queues](https://developer.adobe.com/commerce/php/development/components/message-queues/)
    [Install and configure RabbitMQ](https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/prerequisites/rabbitmq.html)

5. Start the Magento consumer to process messages from the queue:
```
bin/magento queue:consumers:start magendoo_productdata_consumer
```

## Configuration
To configure the Magendoo_ProductWebhook module, follow these steps:

Log in to the Magento admin panel.

Navigate to *Stores > Settings > Configuration > Magendoo > Product Webhook*.

In the General section, you can configure the following settings:

**Enabled**: Enable or disable the module.
**Endpoint**: The URL of the third-party endpoint where product data will be sent.
**Use Queue**: Enable this option to send product data to a message queue before sending it to the third-party endpoint.
