<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Test\Unit\Model\Retry;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterface;
use Ahmed\ErpSyncMonitor\Api\SyncItemRepositoryInterface;
use Ahmed\ErpSyncMonitor\Model\Queue\Topic;
use Ahmed\ErpSyncMonitor\Model\ResourceModel\SyncItem as SyncItemResource;
use Ahmed\ErpSyncMonitor\Model\Retry\RetryService;
use Ahmed\ErpSyncMonitor\Model\Status;
use Ahmed\ErpSyncMonitor\Model\SyncItem;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RetryServiceTest extends TestCase
{
    /**
     * @var SyncItemRepositoryInterface&MockObject
     */
    private SyncItemRepositoryInterface $repository;

    /**
     * @var PublisherInterface&MockObject
     */
    private PublisherInterface $publisher;

    /**
     * @var SyncItem[] keyed by item id; ids missing here are unknown to the repository
     */
    private array $items = [];

    /**
     * Item ids in the order they were published.
     *
     * @var int[]
     */
    private array $publishedIds = [];

    /**
     * @var RetryService
     */
    private RetryService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SyncItemRepositoryInterface::class);
        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->repository->method('getById')->willReturnCallback(
            function (int $itemId): SyncItemInterface {
                if (!isset($this->items[$itemId])) {
                    throw new NoSuchEntityException(__('Sync item with id "%1" does not exist.', $itemId));
                }
                return $this->items[$itemId];
            }
        );
        $this->repository->method('save')->willReturnArgument(0);
        $this->publisher->method('publish')->willReturnCallback(
            function (string $topic, int $itemId): void {
                $this->assertSame(Topic::SYNC_ITEM, $topic);
                $this->publishedIds[] = $itemId;
            }
        );

        $this->service = new RetryService(
            $this->repository,
            $this->publisher,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testRetryResetsAndRequeuesEveryItem(): void
    {
        $failed = $this->givenItem(1, Status::FAILED, 3, 'ERP down');
        $stuck = $this->givenItem(2, Status::PROCESSING, 1, 'timeout');

        $result = $this->service->retry([1, 2]);

        $this->assertSame(2, $result->getRetriedCount());
        $this->assertSame([], $result->getErrors());
        $this->assertSame([1, 2], $this->publishedIds);
        foreach ([$failed, $stuck] as $item) {
            $this->assertSame(Status::PENDING, $item->getStatus());
            $this->assertSame(0, $item->getAttempts());
            $this->assertNull($item->getLastError());
        }
    }

    public function testItemIsSavedBeforeItIsPublished(): void
    {
        $this->givenItem(1, Status::FAILED, 3, 'ERP down');
        $calls = [];
        $this->repository->expects($this->once())->method('save')->willReturnCallback(
            function (SyncItemInterface $item) use (&$calls): SyncItemInterface {
                $calls[] = 'save:' . $item->getStatus();
                return $item;
            }
        );
        $this->publisher->expects($this->once())->method('publish')->willReturnCallback(
            function () use (&$calls): void {
                $calls[] = 'publish';
            }
        );

        $this->service->retry([1]);

        $this->assertSame(['save:' . Status::PENDING, 'publish'], $calls);
    }

    public function testMissingItemIsReportedAndOthersContinue(): void
    {
        $this->givenItem(1, Status::FAILED, 3, 'ERP down');
        $this->givenItem(3, Status::FAILED, 3, 'ERP down');

        $result = $this->service->retry([1, 2, 3]);

        $this->assertSame(2, $result->getRetriedCount());
        $this->assertSame([2], array_keys($result->getErrors()));
        $this->assertStringContainsString('does not exist', $result->getErrors()[2]);
        $this->assertSame([1, 3], $this->publishedIds);
    }

    public function testPublishFailureIsReportedAndOthersContinue(): void
    {
        $this->givenItem(1, Status::FAILED, 3, 'ERP down');
        $this->givenItem(2, Status::FAILED, 3, 'ERP down');
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->publisher->method('publish')->willReturnCallback(
            function (string $topic, int $itemId): void {
                if ($itemId === 1) {
                    throw new \RuntimeException('Queue unavailable');
                }
                $this->publishedIds[] = $itemId;
            }
        );
        $service = new RetryService(
            $this->repository,
            $this->publisher,
            $this->createMock(LoggerInterface::class)
        );

        $result = $service->retry([1, 2]);

        $this->assertSame(1, $result->getRetriedCount());
        $this->assertSame([1 => 'Queue unavailable'], $result->getErrors());
        $this->assertSame([2], $this->publishedIds);
    }

    private function givenItem(int $itemId, string $status, int $attempts, ?string $lastError): SyncItem
    {
        $resource = $this->createMock(SyncItemResource::class);
        $resource->method('getIdFieldName')->willReturn(SyncItemInterface::ITEM_ID);

        $item = new SyncItem($this->createMock(Context::class), $this->createMock(Registry::class), $resource);
        $item->setData(SyncItemInterface::ITEM_ID, $itemId);
        $item->setStatus($status);
        $item->setAttempts($attempts);
        $item->setLastError($lastError);

        return $this->items[$itemId] = $item;
    }
}
