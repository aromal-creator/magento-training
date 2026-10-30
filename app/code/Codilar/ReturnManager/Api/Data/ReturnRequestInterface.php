<?php

namespace Codilar\ReturnManager\Api\Data;

/**
 * Column names of the codilar_return_request table.
 */
interface ReturnRequestInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const PRODUCT_SKU = 'product_sku';
    public const REASON = 'reason';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';

    /**
     * @return int|null
     */
    public function getId();

    public function getOrderId(): ?string;

    public function setOrderId(string $orderId): self;

    public function getProductSku(): ?string;

    public function setProductSku(string $productSku): self;

    public function getReason(): ?string;

    public function setReason(string $reason): self;

    public function getStatus(): ?string;

    public function setStatus(string $status): self;

    public function getCreatedAt(): ?string;
}
