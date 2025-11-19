<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects;

use KitLoong\MigrationsGenerator\Schema\Models\ForeignKey;
use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Contracts\NamingStrategyInterface;
use Pepijnolivier\EloquentModelGenerator\Contracts\RelationInterface;
use Pepijnolivier\EloquentModelGenerator\Contracts\RelationValueObjectInterface;
use Pepijnolivier\EloquentModelGenerator\Relations\SchemaRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasOneRelation;

class BelongsToRelationVO implements RelationValueObjectInterface
{

    protected string $fkLocalColumn;
    protected string $fkForeignColumn;

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

        $this->fkLocalColumn = $foreignKey->getLocalColumns()[0];
        $this->fkForeignColumn = $foreignKey->getForeignColumns()[0];
    }

    // -----


    public function getModelName(): string {
        $tableName = $this->foreignKey->getForeignTableName();
        return $this->namingStrategy::generateModelNameFromTableName($this->schema, $this->relations, $tableName);
    }

    public function getFunctionName(): string {
        return $this->namingStrategy::generateBelongsToFunctionName($this);
    }

    public function getRelation(): BelongsToRelation {
        $functionName = $this->getFunctionName();
        $modelName = $this->getModelName();
        $fkForeignColumn = $this->getFkForeignColumn();
        $fkLocalColumn = $this->getFkLocalColumn();

        return new BelongsToRelation($functionName, $modelName, $fkLocalColumn, $fkForeignColumn);
    }

    public function getFkLocalColumn(): string
    {
        return $this->fkLocalColumn;
    }

    public function getFkForeignColumn(): string
    {
        return $this->fkForeignColumn;
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
