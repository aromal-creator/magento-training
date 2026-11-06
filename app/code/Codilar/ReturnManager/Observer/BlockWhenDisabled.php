<?php

namespace Codilar\ReturnManager\Observer;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Stops every Return Manager page when the module is switched off in configuration.
 *
 * Event data passed by the dispatcher: 'controller_action' (the controller about to run).
 */
class BlockWhenDisabled implements ObserverInterface
{
    public const XML_PATH_ENABLED = 'codilar_return_manager/general/enabled';

    private ScopeConfigInterface $scopeConfig;
    private ActionFlag $actionFlag;
    private ManagerInterface $messageManager;
    private UrlInterface $backendUrl;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ActionFlag $actionFlag,
        ManagerInterface $messageManager,
        UrlInterface $backendUrl
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->actionFlag = $actionFlag;
        $this->messageManager = $messageManager;
        $this->backendUrl = $backendUrl;
    }

    public function execute(Observer $observer)
    {
        if ($this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED)) {
            return;
        }

        // Tell Magento not to run the controller's execute(), then send the admin to the dashboard
        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
        $this->messageManager->addErrorMessage(
            __('Return Manager is disabled. Enable it in Stores > Configuration > Codilar > Return Manager.')
        );
        $observer->getEvent()->getData('controller_action')->getResponse()
            ->setRedirect($this->backendUrl->getUrl('adminhtml/dashboard'));
    }
}
