<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects;

use KitLoong\MigrationsGenerator\Schema\Models\ForeignKey;
use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Contracts\NamingStrategyInterface;
use Pepijnolivier\EloquentModelGenerator\Contracts\RelationValueObjectInterface;
use Pepijnolivier\EloquentModelGenerator\Relations\SchemaRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasOneRelation;

class HasOneRelationVO implements RelationValueObjectInterface
{
    protected string $childTableColumn;
    protected string $parentTableColumn;

    public function __construct(
        protected readonly Schema $schema,
        protected readonly SchemaRelations $relations,
        protected readonly NamingStrategyInterface $namingStrategy,
        protected readonly ForeignKey $foreignKey
    ) {
        $this->validate();
        $this->init();
    }

    protected function validate(): void {
        $localColumns = $this->foreignKey->getLocalColumns();
        $foreignColumns = $this->foreignKey->getForeignColumns();

        if (count($localColumns) !== 1 || count($foreignColumns) !== 1) {
            $baseClass = self::class;
            throw new \InvalidArgumentException("$baseClass only supports single-column foreign keys.");
        }
    }

    protected function init() {
        $foreignKey = $this->foreignKey;

        $this->childTableColumn = $foreignKey->getLocalColumns()[0];
        $this->parentTableColumn = $foreignKey->getForeignColumns()[0];
    }

    public function getModelName(): string {
        $tableName = $this->foreignKey->getTableName();
        return $this->namingStrategy::generateModelNameFromTableName($this->schema, $this->relations, $tableName);
    }

    public function getFunctionName(): string {
        return $this->namingStrategy::generateHasOneFunctionName($this);
    }

    public function getRelation(): HasOneRelation {
        $functionName = $this->getFunctionName();
        $modelName = $this->getModelName();
        $parentTableColumn = $this->getParentTableColumn();
        $childTableColumn = $this->getChildTableColumn();

        return new HasOneRelation($functionName, $modelName, $childTableColumn, $parentTableColumn);
    }

    public function getChildTableColumn(): string
    {
        return $this->childTableColumn;
    }

    public function getParentTableColumn(): string
    {
        return $this->parentTableColumn;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getRelations(): SchemaRelations
    {
        return $this->relations;
    }

    public function getForeignKey(): ForeignKey
    {
        return $this->foreignKey;
    }
}
