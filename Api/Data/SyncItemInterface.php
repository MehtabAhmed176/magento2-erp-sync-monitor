<?php
declare(strict_types=1);

namespace Ahmed\ErpSyncMonitor\Api\Data;

interface SyncItemInterface
{
    public const ITEM_ID = 'item_id';
    public const ENTITY_TYPE = 'entity_type';
    public const ENTITY_ID = 'entity_id';
    public const STATUS = 'status';
    public const ATTEMPTS = 'attempts';
    public const LAST_ERROR = 'last_error';
    public const PAYLOAD = 'payload';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getItemId(): ?int;

    public function getEntityType(): string;

    public function setEntityType(string $entityType): SyncItemInterface;

    public function getSourceEntityId(): int;

    public function setSourceEntityId(int $entityId): SyncItemInterface;

    public function getStatus(): string;

    public function setStatus(string $status): SyncItemInterface;

    public function getAttempts(): int;

    public function setAttempts(int $attempts): SyncItemInterface;

    public function getLastError(): ?string;

    public function setLastError(?string $lastError): SyncItemInterface;

    public function getPayload(): ?string;

    public function setPayload(?string $payload): SyncItemInterface;

    public function getCreatedAt(): ?string;

    public function getUpdatedAt(): ?string;
}