<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model;

final class Status
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const DONE = 'done';
    public const FAILED = 'failed';
}