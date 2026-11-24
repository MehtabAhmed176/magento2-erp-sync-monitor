<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model\Retry;

use Ahmed\ErpSyncMonitor\Api\SyncItemRepositoryInterface;
use Ahmed\ErpSyncMonitor\Model\Queue\Topic;
use Ahmed\ErpSyncMonitor\Model\Status;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Manual retry: gives each item a fresh attempt budget and queues it again.
 */
class RetryService
{
    /**
     * @param SyncItemRepositoryInterface $repository
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SyncItemRepositoryInterface $repository,
        private readonly PublisherInterface $publisher,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * A failing item is recorded in the result and does not stop the remaining items.
     *
     * @param int[] $itemIds
     * @return RetryResult
     */
    public function retry(array $itemIds): RetryResult
    {
        $retriedCount = 0;
        $errors = [];

        foreach ($itemIds as $itemId) {
            try {
                $this->retryItem((int)$itemId);
                $retriedCount++;
            } catch (\Exception $e) {
                $errors[(int)$itemId] = $e->getMessage();
                $this->logger->error(
                    'Manual retry of ERP sync item failed.',
                    ['item_id' => $itemId, 'error' => $e->getMessage()]
                );
            }
        }

        return new RetryResult($retriedCount, $errors);
    }

    /**
     * Persist the reset before publishing so the consumer always sees a pending item.
     *
     * @param int $itemId
     * @return void
     * @throws \Exception
     */
    private function retryItem(int $itemId): void
    {
        $item = $this->repository->getById($itemId);
        $item->setStatus(Status::PENDING);
        $item->setAttempts(0);
        $item->setLastError(null);
        $this->repository->save($item);

        $this->publisher->publish(Topic::SYNC_ITEM, $itemId);
    }
}
