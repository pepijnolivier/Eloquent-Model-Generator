<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects;

use KitLoong\MigrationsGenerator\Schema\Models\ForeignKey;
use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Contracts\NamingStrategyInterface;
use Pepijnolivier\EloquentModelGenerator\Contracts\RelationValueObjectInterface;
use Pepijnolivier\EloquentModelGenerator\Relations\SchemaRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToManyRelation;

/**
 * This Value Object represents a BelongsToMany relation between two models.
 * It actually holds the two foreign keys on the pivot (!) table. (eg user_group)
 *
 * The VO is used in the following way:
 * It is created twice, (second time with fk1 and fk2 swapped)
 * This allows us to generate the BelongsToMany relation from both sides using 1 flow of logic.
 */
class BelongsToManyRelationVO implements RelationValueObjectInterface
{
    public function __construct(
        protected readonly Schema $schema,
        protected readonly SchemaRelations $relations,
        protected readonly NamingStrategyInterface $namingStrategy,
        protected readonly ForeignKey $fk1,
        protected readonly ForeignKey $fk2

    ) {
        $this->validate();
        $this->init();
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getRelations(): SchemaRelations
    {
        return $this->relations;
    }

    public function getNamingStrategy(): NamingStrategyInterface
    {
        return $this->namingStrategy;
    }

    public function getFk1(): ForeignKey
    {
        return $this->fk1;
    }

    public function getFk2(): ForeignKey
    {
        return $this->fk2;
    }

    protected function validate(): void {

        $fks = [$this->fk1, $this->fk2];

        foreach($fks as $foreignKey) {
            $localColumns = $foreignKey->getLocalColumns();
            $foreignColumns = $foreignKey->getForeignColumns();

            if (count($localColumns) !== 1 || count($foreignColumns) !== 1) {
                $baseClass = self::class;
                throw new \InvalidArgumentException("$baseClass only supports single-column foreign keys.");
            }
        }


        // fk1 tablename should also be the same as fk2 tablename
        if ($this->fk1->getTableName() !== $this->fk2->getTableName()) {
            $baseClass = self::class;
            throw new \InvalidArgumentException("$baseClass fk1 and fk2 must reference the same table.");
        }
    }

    protected function init() {
    }

    public function getRelation(): BelongsToManyRelation
    {
        $fk1Table = $this->fk1->getTableName();
        $fk2Table = $this->fk2->getForeignTableName();
        $belongsToManyModel = $this->namingStrategy::generateModelNameFromTableName(
            $this->schema,
            $this->relations,
            $fk2Table
        );




        $through = $this->fk1->getTableName();

        $belongsToManyFunctionName = $this->namingStrategy::generateBelongsToManyFunctionName($this);

        $fk1Table = $this->fk1->getForeignTableName();
        $fk1Field = $this->fk1->getLocalColumns()[0];

        $fk2Table = $this->fk2->getForeignTableName();
        $fk2Field = $this->fk2->getLocalColumns()[0];


        $relation = new BelongsToManyRelation($belongsToManyFunctionName, $belongsToManyModel, $through, $fk1Field, $fk2Field);
        return $relation;
    }
}
