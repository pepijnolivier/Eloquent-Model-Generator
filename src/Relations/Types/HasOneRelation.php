<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations\Types;

use Pepijnolivier\EloquentModelGenerator\Traits\HelperTrait;

class HasOneRelation
{
    use HelperTrait;

    public static function fromTable(string $tableName, string $foreignKey, string $localKey): HasOneRelation {
        $modelName = self::generateModelNameFromTableName($tableName);
        $functionName = self::getSingularFunctionName($modelName);
        return new self($functionName, $modelName, $foreignKey, $localKey);
    }

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
