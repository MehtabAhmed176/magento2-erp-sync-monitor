<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Test\Unit\Model\Queue;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterface;
use Ahmed\ErpSyncMonitor\Api\ErpClientInterface;
use Ahmed\ErpSyncMonitor\Api\SyncItemRepositoryInterface;
use Ahmed\ErpSyncMonitor\Model\Queue\SyncItemConsumer;
use Ahmed\ErpSyncMonitor\Model\Queue\Topic;
use Ahmed\ErpSyncMonitor\Model\ResourceModel\SyncItem as SyncItemResource;
use Ahmed\ErpSyncMonitor\Model\Status;
use Ahmed\ErpSyncMonitor\Model\SyncItem;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SyncItemConsumerTest extends TestCase
{
    private const ITEM_ID = 42;

    /**
     * @var SyncItemRepositoryInterface&MockObject
     */
    private SyncItemRepositoryInterface $repository;

    /**
     * @var ErpClientInterface&MockObject
     */
    private ErpClientInterface $erpClient;

    /**
     * @var PublisherInterface&MockObject
     */
    private PublisherInterface $publisher;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * Status persisted by each repository save, in call order.
     *
     * @var string[]
     */
    private array $savedStatuses = [];

    /**
     * @var SyncItemConsumer
     */
    private SyncItemConsumer $consumer;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SyncItemRepositoryInterface::class);
        $this->erpClient = $this->createMock(ErpClientInterface::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->repository->method('save')->willReturnCallback(
            function (SyncItemInterface $item): SyncItemInterface {
                $this->savedStatuses[] = $item->getStatus();
                return $item;
            }
        );

        $this->consumer = new SyncItemConsumer(
            $this->repository,
            $this->erpClient,
            $this->publisher,
            $this->logger
        );
    }

    public function testSuccessfulSendMarksItemDone(): void
    {
        $item = $this->givenItem(Status::PENDING, 1, 'previous timeout');
        $this->erpClient->expects($this->once())->method('send')->with($item);
        $this->publisher->expects($this->never())->method('publish');

        $this->consumer->process(self::ITEM_ID);

        $this->assertSame([Status::PROCESSING, Status::DONE], $this->savedStatuses);
        $this->assertSame(1, $item->getAttempts());
        $this->assertNull($item->getLastError());
    }

    public function testFailureBelowMaxAttemptsRequeuesItem(): void
    {
        $item = $this->givenItem(Status::PENDING, 0);
        $this->erpClient->method('send')->willThrowException(new \RuntimeException('ERP timeout'));
        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(Topic::SYNC_ITEM, self::ITEM_ID)
            ->willReturnCallback(
                function (): void {
                    $this->assertSame(
                        Status::PENDING,
                        end($this->savedStatuses),
                        'Item must be persisted as pending before the retry is published.'
                    );
                }
            );
        $this->logger->expects($this->never())->method('error');

        $this->consumer->process(self::ITEM_ID);

        $this->assertSame([Status::PROCESSING, Status::PENDING], $this->savedStatuses);
        $this->assertSame(1, $item->getAttempts());
        $this->assertSame('ERP timeout', $item->getLastError());
    }

    public function testFailureOnLastAttemptMarksItemFailed(): void
    {
        $item = $this->givenItem(Status::PENDING, SyncItemConsumer::MAX_ATTEMPTS - 1);
        $this->erpClient->method('send')->willThrowException(new \RuntimeException('ERP down'));
        $this->publisher->expects($this->never())->method('publish');
        $this->logger->expects($this->once())->method('error');

        $this->consumer->process(self::ITEM_ID);

        $this->assertSame([Status::PROCESSING, Status::FAILED], $this->savedStatuses);
        $this->assertSame(SyncItemConsumer::MAX_ATTEMPTS, $item->getAttempts());
        $this->assertSame('ERP down', $item->getLastError());
    }

    /**
     * @dataProvider nonPendingStatusProvider
     */
    public function testRedeliveredMessageForNonPendingItemIsIgnored(string $status): void
    {
        $this->givenItem($status, 0);
        $this->erpClient->expects($this->never())->method('send');
        $this->publisher->expects($this->never())->method('publish');

        $this->consumer->process(self::ITEM_ID);

        $this->assertSame([], $this->savedStatuses);
    }

    public static function nonPendingStatusProvider(): array
    {
        return [
            'done' => [Status::DONE],
            'failed' => [Status::FAILED],
            'processing' => [Status::PROCESSING],
        ];
    }

    public function testMessageForMissingItemIsDropped(): void
    {
        $this->repository->method('getById')
            ->willThrowException(new NoSuchEntityException(__('Not found')));
        $this->erpClient->expects($this->never())->method('send');
        $this->logger->expects($this->once())->method('warning');

        $this->consumer->process(self::ITEM_ID);

        $this->assertSame([], $this->savedStatuses);
    }

    private function givenItem(string $status, int $attempts, ?string $lastError = null): SyncItem
    {
        $resource = $this->createMock(SyncItemResource::class);
        $resource->method('getIdFieldName')->willReturn(SyncItemInterface::ITEM_ID);

        $item = new SyncItem($this->createMock(Context::class), $this->createMock(Registry::class), $resource);
        $item->setData(SyncItemInterface::ITEM_ID, self::ITEM_ID);
        $item->setStatus($status);
        $item->setAttempts($attempts);
        $item->setLastError($lastError);

        $this->repository->method('getById')->with(self::ITEM_ID)->willReturn($item);

        return $item;
    }
}
