<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Observer;

use Ahmed\ErpSyncMonitor\Model\Queue\Topic;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Publishes after commit, so a consumer can never receive the id of an uncommitted outbox row.
 */
class PublishOrderSyncItem implements ObserverInterface
{
    private const ORDER_SYNC_ITEM_PUBLISHED = 'ahmed_erp_sync_item_published';

    /**
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * A publish failure must not break checkout: the order is already committed and the item stays pending.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        $itemId = (int)$order->getData(CreateOrderSyncItem::ORDER_SYNC_ITEM_ID);

        if ($itemId === 0 || $order->getData(self::ORDER_SYNC_ITEM_PUBLISHED)) {
            return;
        }
        $order->setData(self::ORDER_SYNC_ITEM_PUBLISHED, true);

        try {
            $this->publisher->publish(Topic::SYNC_ITEM, $itemId);
        } catch (\Exception $e) {
            $this->logger->error(
                'Could not publish ERP sync item, it stays pending.',
                ['item_id' => $itemId, 'order_id' => $order->getEntityId(), 'error' => $e->getMessage()]
            );
        }
    }
}
