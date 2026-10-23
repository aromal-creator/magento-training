<?php

namespace Codilar\DashboardProducts\ViewModel;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ProductList implements ArgumentInterface
{
    private const LIMIT = 10;

    private CollectionFactory $productCollectionFactory;
    private PriceCurrencyInterface $priceCurrency;
    private UrlInterface $backendUrl;
    private AuthorizationInterface $authorization;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        UrlInterface $backendUrl,
        AuthorizationInterface $authorization
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->priceCurrency = $priceCurrency;
        $this->backendUrl = $backendUrl;
        $this->authorization = $authorization;
    }

    public function isAllowed(): bool
    {
        return $this->authorization->isAllowed('Magento_Catalog::products');
    }

    public function getProducts(): Collection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', 'status'])
            ->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(self::LIMIT);

        return $collection;
    }

    public function getTotalCount(): int
    {
        return $this->productCollectionFactory->create()->getSize();
    }

    public function formatPrice(Product $product): string
    {
        return $this->priceCurrency->format($product->getPrice(), false);
    }

    public function isEnabled(Product $product): bool
    {
        return (int) $product->getStatus() === Status::STATUS_ENABLED;
    }

    public function getEditUrl(Product $product): string
    {
        return $this->backendUrl->getUrl('catalog/product/edit', ['id' => $product->getId()]);
    }

    public function getAllProductsUrl(): string
    {
        return $this->backendUrl->getUrl('catalog/product/index');
    }
}
