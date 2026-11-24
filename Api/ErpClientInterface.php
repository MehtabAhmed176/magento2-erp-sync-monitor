<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Api;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterface;

interface ErpClientInterface
{
    /**
     * Transfer a sync item to the ERP; any exception means the transfer did not succeed.
     *
     * @param SyncItemInterface $item
     * @return void
     * @throws \Exception
     */
    public function send(SyncItemInterface $item): void;
}
