<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations\Types;

class BelongsToManyRelation
{
    public function __construct(
        protected string  $functionName,
        protected string  $entityClass,
        protected ?string $table = null,
        protected ?string $foreignPivotKey = null,
        protected ?string $relatedPivotKey = null,
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

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getForeignPivotKey(): ?string
    {
        return $this->foreignPivotKey;
    }

    public function getRelatedPivotKey(): ?string
    {
        return $this->relatedPivotKey;
    }

}
