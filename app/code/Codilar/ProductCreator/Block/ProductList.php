<?php

namespace Codilar\ProductCreator\Block;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;

class ProductList extends Template
{
    private CollectionFactory $productCollectionFactory;
    private PriceCurrencyInterface $priceCurrency;
    private Visibility $visibility;
    private ?Collection $products = null;

    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        Visibility $visibility,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->productCollectionFactory = $productCollectionFactory;
        $this->priceCurrency = $priceCurrency;
        $this->visibility = $visibility;
    }

    

    public function getProducts(): Collection
    {
        if ($this->products === null) {
            $this->products = $this->productCollectionFactory->create();
            $this->products->addAttributeToSelect(['name', 'price', 'status', 'visibility', 'url_key']);
            $this->products->setOrder('entity_id', 'DESC');
        }

        return $this->products;
    }



    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        /** @var Pager $pager */
        $pager = $this->getLayout()->createBlock(Pager::class, 'productcreator.product.list.pager');
        $pager->setAvailableLimit([20 => 20, 50 => 50, 100 => 100])
            ->setShowPerPage(true)
            ->setCollection($this->getProducts());
        $this->setChild('pager', $pager);

        return $this;
    }

    public function getPagerHtml(): string
    {
        return $this->getChildHtml('pager');
    }

    public function formatPrice(Product $product): string
    {
        return $this->priceCurrency->format($product->getPrice(), false);
    }

    public function isEnabled(Product $product): bool
    {
        return (int) $product->getStatus() === Status::STATUS_ENABLED;
    }

    public function canView(Product $product): bool
    {
        return $this->isEnabled($product)
            && in_array((int) $product->getVisibility(), $this->visibility->getVisibleInSiteIds(), true);
    }

    public function getCreateUrl(): string
    {
        return $this->getUrl('shoes.html');
    }
}
