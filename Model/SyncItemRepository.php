<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterface;
use Ahmed\ErpSyncMonitor\Api\SyncItemRepositoryInterface;
use Ahmed\ErpSyncMonitor\Model\ResourceModel\SyncItem as SyncItemResource;
use Ahmed\ErpSyncMonitor\Model\ResourceModel\SyncItem\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SyncItemRepository implements SyncItemRepositoryInterface
{
    public function __construct(
        private readonly SyncItemResource $resource,
        private readonly SyncItemFactory $itemFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(SyncItemInterface $item): SyncItemInterface
    {
        try {
            /** @var SyncItem $item */
            $this->resource->save($item);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save sync item: %1', $e->getMessage()), $e);
        }

        return $item;
    }

    public function getById(int $itemId): SyncItemInterface
    {
        $item = $this->itemFactory->create();
        $this->resource->load($item, $itemId);

        if (!$item->getId()) {
            throw new NoSuchEntityException(__('Sync item with id "%1" does not exist.', $itemId));
        }

        return $item;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(SyncItemInterface $item): void
    {
        try {
            /** @var SyncItem $item */
            $this->resource->delete($item);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete sync item: %1', $e->getMessage()), $e);
        }
    }
}