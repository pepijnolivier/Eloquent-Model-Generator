<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations\Types;

use Pepijnolivier\EloquentModelGenerator\Traits\HelperTrait;

class HasManyRelation
{
    use HelperTrait;

    public static function fromTable(string $tableName, string $foreignKey, string $localKey): HasManyRelation {
        $hasManyModel = self::generateModelNameFromTableName($tableName);
        $hasManyFunctionName = self::getPluralFunctionName($hasManyModel);

        // @toconsider: if it's a table with only 2 columns, and they are both the FK
        // then it's just a pure pivot table. We might not want to add a relation for that.

        return new HasManyRelation($hasManyFunctionName, $hasManyModel, $foreignKey, $localKey);

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
