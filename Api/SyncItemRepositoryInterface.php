<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Api;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SyncItemRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(SyncItemInterface $item): SyncItemInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $itemId): SyncItemInterface;

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(SyncItemInterface $item): void;
}