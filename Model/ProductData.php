<?php
declare(strict_types=1);

namespace Magendoo\ProductWebhook\Model;

use Magendoo\ProductWebhook\Api\Data\ProductDataInterface;
use Magento\Framework\DataObject;

/**
 * Product data model for message queue
 */
class ProductData extends DataObject implements ProductDataInterface
{
    /**
     * @inheritdoc
     */
    public function getEntityId(): ?int
    {
        return $this->getData('entity_id') ? (int) $this->getData('entity_id') : null;
    }

    /**
     * @inheritdoc
     */
    public function setEntityId(int $entityId): ProductDataInterface
    {
        return $this->setData('entity_id', $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        return $this->getData('store_id') ? (int) $this->getData('store_id') : null;
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(int $storeId): ProductDataInterface
    {
        return $this->setData('store_id', $storeId);
    }
}
