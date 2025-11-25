<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations\Types;

use Pepijnolivier\EloquentModelGenerator\Contracts\RelationInterface;
use Pepijnolivier\EloquentModelGenerator\Traits\HelperTrait;

class BelongsToRelation implements RelationInterface
{

    public function __construct(
        protected string $functionName,
        protected string $entityClass,
        protected ?string $foreignKey = null,
        protected ?string $ownerKey = null
    )
    {
    }

    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }

    public function getOwnerKey(): ?string
    {
        return $this->ownerKey;
    }
}
