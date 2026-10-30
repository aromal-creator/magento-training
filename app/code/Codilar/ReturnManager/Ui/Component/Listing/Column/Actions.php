<?php

namespace Codilar\ReturnManager\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Adds the Edit and Delete links to each row of the grid.
 */
class Actions extends Column
{
    private const URL_PATH_EDIT = 'returnmanager/request/edit';
    private const URL_PATH_DELETE = 'returnmanager/request/delete';

    private UrlInterface $urlBuilder;
    private Escaper $escaper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['entity_id'])) {
                continue;
            }
            $id = $item['entity_id'];
            $item[$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['id' => $id]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['id' => $id]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Return #%1', $this->escaper->escapeHtml($id)),
                        'message' => __('Are you sure you want to delete return request #%1?', $this->escaper->escapeHtml($id)),
                    ],
                    'post' => true,
                ],
            ];
        }

        return $dataSource;
    }
}
