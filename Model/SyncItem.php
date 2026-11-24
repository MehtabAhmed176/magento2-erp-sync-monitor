<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Model;

use Ahmed\ErpSyncMonitor\Api\Data\SyncItemInterface;
use Ahmed\ErpSyncMonitor\Model\ResourceModel\SyncItem as SyncItemResource;
use Magento\Framework\Model\AbstractModel;

class SyncItem extends AbstractModel implements SyncItemInterface
{
    protected function _construct(): void
    {
        $this->_init(SyncItemResource::class);
    }

    public function getItemId(): ?int
    {
        $id = $this->getData(self::ITEM_ID);
        return $id === null ? null : (int)$id;
    }

    public function getEntityType(): string
    {
        return (string)$this->getData(self::ENTITY_TYPE);
    }

    public function setEntityType(string $entityType): SyncItemInterface
    {
        return $this->setData(self::ENTITY_TYPE, $entityType);
    }

    public function getSourceEntityId(): int
    {
        return (int)$this->getData(self::ENTITY_ID);
    }

    public function setSourceEntityId(int $entityId): SyncItemInterface
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }
    

    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus(string $status): SyncItemInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getAttempts(): int
    {
        return (int)$this->getData(self::ATTEMPTS);
    }

    public function setAttempts(int $attempts): SyncItemInterface
    {
        return $this->setData(self::ATTEMPTS, $attempts);
    }

    public function getLastError(): ?string
    {
        return $this->getData(self::LAST_ERROR);
    }

    public function setLastError(?string $lastError): SyncItemInterface
    {
        return $this->setData(self::LAST_ERROR, $lastError);
    }

    public function getPayload(): ?string
    {
        return $this->getData(self::PAYLOAD);
    }

    public function setPayload(?string $payload): SyncItemInterface
    {
        return $this->setData(self::PAYLOAD, $payload);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }
}