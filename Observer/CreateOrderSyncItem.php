<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Observer;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterfaceFactory;
use Ahmed\ErpSyncMonitor\Api\SyncItemRepositoryInterface;
use Ahmed\ErpSyncMonitor\Model\Status;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * Writes the outbox row inside the order save transaction, so both commit or roll back together.
 */
class CreateOrderSyncItem implements ObserverInterface
{
    public const ENTITY_TYPE = 'order';

    /**
     * Order data key holding the created sync item id; read by the publishing observer.
     */
    public const ORDER_SYNC_ITEM_ID = 'ahmed_erp_sync_item_id';

    /**
     * @param SyncItemInterfaceFactory $itemFactory
     * @param SyncItemRepositoryInterface $repository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly SyncItemInterfaceFactory $itemFactory,
        private readonly SyncItemRepositoryInterface $repository,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Only the first save of an order creates an item; empty orig entity_id means it was never persisted before.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');

        if ($order->getOrigData(OrderInterface::ENTITY_ID) !== null
            || $order->hasData(self::ORDER_SYNC_ITEM_ID)
        ) {
            return;
        }

        $item = $this->itemFactory->create();
        $item->setEntityType(self::ENTITY_TYPE);
        $item->setSourceEntityId((int)$order->getEntityId());
        $item->setStatus(Status::PENDING);
        $item->setPayload($this->serializer->serialize([
            'increment_id' => $order->getIncrementId(),
            'grand_total' => (float)$order->getGrandTotal(),
        ]));
        $this->repository->save($item);

        $order->setData(self::ORDER_SYNC_ITEM_ID, $item->getItemId());
    }
}
