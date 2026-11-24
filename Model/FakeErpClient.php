<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterface;
use Ahmed\ErpSyncMonitor\Api\ErpClientInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Stand-in for the Odoo client that fails randomly to exercise the retry flow.
 */
class FakeErpClient implements ErpClientInterface
{
    /**
     * @param int $failureRatePercent
     */
    public function __construct(
        private readonly int $failureRatePercent = 40
    ) {
    }

    /**
     * @inheritdoc
     */
    public function send(SyncItemInterface $item): void
    {
        if (random_int(1, 100) <= $this->failureRatePercent) {
            throw new LocalizedException(
                __('Simulated ERP timeout for sync item %1.', $item->getItemId())
            );
        }
    }
}
