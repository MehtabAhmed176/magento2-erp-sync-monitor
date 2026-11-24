<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model\ResourceModel\SyncItem;

use Ahmed\ErpSyncMonitor\Model\ResourceModel\SyncItem as SyncItemResource;
use Ahmed\ErpSyncMonitor\Model\SyncItem;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'item_id';

    protected function _construct(): void
    {
        $this->_init(SyncItem::class, SyncItemResource::class);
    }
}