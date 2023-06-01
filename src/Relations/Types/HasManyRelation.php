<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations\Types;

class HasManyRelation
{
    public function __construct(
        protected string $functionName,
        protected string $entityClass,
        protected ?string $foreignKey = null,
        protected ?string $localKey = null,
    ) {

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

    public function getLocalKey(): ?string
    {
        return $this->localKey;
    }


}
