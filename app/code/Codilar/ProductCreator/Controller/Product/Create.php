<?php

namespace Codilar\ProductCreator\Controller\Product;

use Codilar\ProductCreator\Model\ProductRecordFactory;
use Codilar\ProductCreator\Model\ResourceModel\ProductRecord
    as ProductRecordResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

class Create extends Action implements HttpGetActionInterface
{
    private ProductFactory $productFactory;
    private ProductRepositoryInterface $productRepository;
    private StoreManagerInterface $storeManager;
    private ProductRecordFactory $recordFactory;
    private ProductRecordResource $recordResource;
    private PageFactory $pageFactory;

    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        ProductRecordFactory $recordFactory,
        ProductRecordResource $recordResource,
        PageFactory $pageFactory
    ) {
        parent::__construct($context);

        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->recordFactory = $recordFactory;
        $this->recordResource = $recordResource;
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Create Product'));
        $block = $page->getLayout()->getBlock('productcreator.product.create');
        $sku = 'custom-module-tshirt-' . date('YmdHis');

        try {
            $product = $this->productFactory->create();

            $product->setStoreId(0);
            $product->setWebsiteIds([
                (int) $this->storeManager->getWebsite()->getId()
            ]);
            $product->setAttributeSetId(
                $product->getDefaultAttributeSetId()
            );
            $product->setTypeId('simple');
            $product->setSku($sku);
            $product->setName('Custom Module T-Shirt');
            $product->setUrlKey($sku);
            $product->setPrice(999);
            $product->setWeight(0.5);
            $product->setStatus(1);
            $product->setVisibility(4);
            $product->setTaxClassId(0);
            $product->setStockData([
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_in_stock' => 1
            ]);

            $savedProduct = $this->productRepository->save($product);

            $record = $this->recordFactory->create();
            $record->setData([
                'product_id' => $savedProduct->getId(),
                'sku' => $savedProduct->getSku()
            ]);

            $this->recordResource->save($record);

            $block->setData('success', true);
            $block->setData('product', $savedProduct);
        } catch (\Throwable $e) {
            $block->setData('success', false);
            $block->setData('error', $e->getMessage());
        }

        return $page;
    }
}
