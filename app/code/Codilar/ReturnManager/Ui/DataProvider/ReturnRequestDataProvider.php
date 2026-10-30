<?php

namespace Codilar\ReturnManager\Ui\DataProvider;

use Codilar\ReturnManager\Controller\Adminhtml\Request\Save;
use Codilar\ReturnManager\Model\ResourceModel\ReturnRequest\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Supplies the add/edit form with the return request's saved values.
 */
class ReturnRequestDataProvider extends AbstractDataProvider
{
    private DataPersistorInterface $dataPersistor;
    private ?array $loadedData = null;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        foreach ($this->collection->getItems() as $returnRequest) {
            $this->loadedData[$returnRequest->getId()] = $returnRequest->getData();
        }

        // After a failed save, show what the admin typed instead of the saved values
        $persisted = $this->dataPersistor->get(Save::DATA_PERSISTOR_KEY);
        if (!empty($persisted)) {
            $returnRequest = $this->collection->getNewEmptyItem();
            $returnRequest->setData($persisted);
            // A new (unsaved) request has no ID; the form looks it up under ''
            $this->loadedData[$returnRequest->getId() ?? ''] = $returnRequest->getData();
            $this->dataPersistor->clear(Save::DATA_PERSISTOR_KEY);
        }

        return $this->loadedData;
    }
}
