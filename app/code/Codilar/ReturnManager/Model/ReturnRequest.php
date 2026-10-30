<?php

namespace Codilar\ReturnManager\Model;

use Codilar\ReturnManager\Api\Data\ReturnRequestInterface;
use Codilar\ReturnManager\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use Magento\Framework\Model\AbstractModel;

/**
 * One return request (one row of codilar_return_request).
 */
class ReturnRequest extends AbstractModel implements ReturnRequestInterface
{
    protected $_eventPrefix = 'codilar_return_request';

    protected function _construct()
    {
        $this->_init(ReturnRequestResource::class);
    }

    public function getOrderId(): ?string
    {
        return $this->getData(self::ORDER_ID);
    }

    public function setOrderId(string $orderId): ReturnRequestInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function getProductSku(): ?string
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    public function setProductSku(string $productSku): ReturnRequestInterface
    {
        return $this->setData(self::PRODUCT_SKU, $productSku);
    }

    public function getReason(): ?string
    {
        return $this->getData(self::REASON);
    }

    public function setReason(string $reason): ReturnRequestInterface
    {
        return $this->setData(self::REASON, $reason);
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus(string $status): ReturnRequestInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
