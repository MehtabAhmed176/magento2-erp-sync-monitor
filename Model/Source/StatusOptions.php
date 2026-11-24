<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model\Source;

use Ahmed\ErpSyncMonitor\Model\Status;
use Magento\Framework\Data\OptionSourceInterface;

class StatusOptions implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Status::PENDING, 'label' => __('Pending')],
            ['value' => Status::PROCESSING, 'label' => __('Processing')],
            ['value' => Status::DONE, 'label' => __('Done')],
            ['value' => Status::FAILED, 'label' => __('Failed')],
        ];
    }
}
