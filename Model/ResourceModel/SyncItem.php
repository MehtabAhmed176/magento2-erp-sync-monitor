<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SyncItem extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('ahmed_erp_sync_item', 'item_id');
    }
}