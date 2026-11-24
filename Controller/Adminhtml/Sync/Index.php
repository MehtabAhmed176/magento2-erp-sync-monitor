<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Ahmed_ErpSyncMonitor::sync_items';

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        /** @var Page $page */
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Ahmed_ErpSyncMonitor::sync_items');
        $page->getConfig()->getTitle()->prepend(__('ERP Sync Items'));

        return $page;
    }
}
