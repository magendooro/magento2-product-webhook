<?php
declare(strict_types=1);

namespace Magendoo\ProductWebhook\Api\Data;

/**
 * Product data interface for message queue
 *
 * @api
 */
interface ProductDataInterface
{
    /**
     * Get product ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Set product ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId(int $entityId): self;

    /**
     * Get store ID
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * Get product data as array
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Set product data
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data): self;
}
