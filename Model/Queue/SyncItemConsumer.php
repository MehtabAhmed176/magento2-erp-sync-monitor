<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model\Queue;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterface;
use Ahmed\ErpSyncMonitor\Api\ErpClientInterface;
use Ahmed\ErpSyncMonitor\Api\SyncItemRepositoryInterface;
use Ahmed\ErpSyncMonitor\Model\Status;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

class SyncItemConsumer
{
    public const MAX_ATTEMPTS = 3;

    /**
     * @param SyncItemRepositoryInterface $repository
     * @param ErpClientInterface $erpClient
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SyncItemRepositoryInterface $repository,
        private readonly ErpClientInterface $erpClient,
        private readonly PublisherInterface $publisher,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process one queue message; the queue delivers at least once, so only pending items are sent.
     *
     * @param int $itemId
     * @return void
     */
    public function process(int $itemId): void
    {
        try {
            $item = $this->repository->getById($itemId);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('ERP sync item not found, message dropped.', ['item_id' => $itemId]);
            return;
        }

        if ($item->getStatus() !== Status::PENDING) {
            return;
        }

        $item->setStatus(Status::PROCESSING);
        $this->repository->save($item);

        try {
            $this->erpClient->send($item);
        } catch (\Exception $e) {
            $this->handleFailure($item, $e);
            return;
        }

        $item->setStatus(Status::DONE);
        $item->setLastError(null);
        $this->repository->save($item);
    }

    /**
     * Record the failed attempt, then either schedule a retry or mark the item as failed.
     *
     * @param SyncItemInterface $item
     * @param \Exception $exception
     * @return void
     */
    private function handleFailure(SyncItemInterface $item, \Exception $exception): void
    {
        $attempts = $item->getAttempts() + 1;
        $item->setAttempts($attempts);
        $item->setLastError($exception->getMessage());

        if ($attempts < self::MAX_ATTEMPTS) {
            $item->setStatus(Status::PENDING);
            $this->repository->save($item);
            $this->publisher->publish(Topic::SYNC_ITEM, $item->getItemId());
            return;
        }

        $item->setStatus(Status::FAILED);
        $this->repository->save($item);
        $this->logger->error(
            'ERP sync item failed permanently.',
            ['item_id' => $item->getItemId(), 'attempts' => $attempts, 'error' => $exception->getMessage()]
        );
    }
}
