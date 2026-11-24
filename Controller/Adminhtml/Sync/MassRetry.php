<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Controller\Adminhtml\Sync;

use Ahmed\ErpSyncMonitor\Model\ResourceModel\SyncItem\CollectionFactory;
use Ahmed\ErpSyncMonitor\Model\Retry\RetryService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

class MassRetry extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Ahmed_ErpSyncMonitor::sync_items_retry';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param RetryService $retryService
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly RetryService $retryService
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/index');

        try {
            $itemIds = $this->filter->getCollection($this->collectionFactory->create())->getAllIds();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect;
        }

        $result = $this->retryService->retry(array_map('intval', $itemIds));

        if ($result->getRetriedCount() > 0) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 sync item(s) have been queued for retry.', $result->getRetriedCount())
            );
        }
        foreach ($result->getErrors() as $itemId => $error) {
            $this->messageManager->addErrorMessage(
                __('Sync item %1 could not be retried: %2', $itemId, $error)
            );
        }

        return $redirect;
    }
}
