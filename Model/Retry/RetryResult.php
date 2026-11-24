<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model\Retry;

class RetryResult
{
    /**
     * @param int $retriedCount
     * @param array<int, string> $errors error message per item id
     */
    public function __construct(
        private readonly int $retriedCount,
        private readonly array $errors
    ) {
    }

    /**
     * Number of items reset and queued again.
     *
     * @return int
     */
    public function getRetriedCount(): int
    {
        return $this->retriedCount;
    }

    /**
     * Error message per item id for items that could not be retried.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
