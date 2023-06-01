<?php

namespace Pepijnolivier\EloquentModelGenerator\Parser;
use Illuminate\Support\Collection;
use KitLoong\MigrationsGenerator\Enum\Migrations\Method\IndexType;
use KitLoong\MigrationsGenerator\Schema\Models\ForeignKey;
use KitLoong\MigrationsGenerator\Schema\Models\Index;
use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Relations\TableRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToManyRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasManyRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasOneRelation;
use Pepijnolivier\EloquentModelGenerator\Traits\HelperTrait;

class RelationsParser
{
    /** @var Schema $schema */
    protected Schema $schema;

    /** @var TableRelations[] $relationsPerTable */
    private array $relationsPerTable;

    use HelperTrait;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
        $this->relationsPerTable = $this->parse();
    }

    public function getRelationsForTable(string $table): ?TableRelations
    {
        return $this->relationsPerTable[$table] ?? null;
    }

    /** @return TableRelations[] */
    public function getRelationsPerTable(): array
    {
        return $this->relationsPerTable;
    }

    private function parse()
    {
        $tables = $this->schema->getTableNames();

        /** @var TableRelations[] $relationsByTable */
        $relationsByTable = [];

        // first create empty ruleset for each table
        foreach ($tables as $tableName) {
            $relationsByTable[$tableName] = new TableRelations($tableName);
        }

        /** @var string $tableName */
        foreach ($tables as $tableName) {
            $table = $this->schema->getTable($tableName);
            $foreign = $this->getForeignKeysForTable($tableName);
            $primary = $this->getPrimaryKeysForTable($tableName);

            // @improvement we should probably pass in the table everywhere, instead of the table name...
            $isManyToMany = $this->isManyToManyTable($tableName);

            if ($isManyToMany === true) {
                $this->addManyToManyRelations($tableName, $relationsByTable);
            }

            foreach ($foreign as $fk) {
                $isOneToOne = $this->isOneToOne($fk, $primary);

                if ($isOneToOne) {
                    $this->addOneToOneRules($tableName, $fk, $relationsByTable);
                } else {
                    $this->addOneToManyRules($tableName, $fk, $relationsByTable);
                }
            }
        }

        return $relationsByTable;
    }

    private function addOneToManyRules(string $tableName, ForeignKey $fk, array &$relationsByTable)
    {
        // $table belongs to $FK
        // FK hasMany $table

        $tableNames = $this->schema->getTableNames()->toArray();
        $fkTable = $fk->getForeignTableName();

        $localColumns = $fk->getLocalColumns();
        $foreignColumns = $fk->getForeignColumns();

        if(count($localColumns) > 1) {
            throw new \Exception('Composite local keys are not supported');
        }

        if(count($foreignColumns) > 1) {
            throw new \Exception('Composite foreign columns are not supported');
        }

        $field = $localColumns[0];
        $references = $foreignColumns[0];

        if(in_array($fkTable, $tableNames)) {
            $hasManyModel = $this->generateModelNameFromTableName($tableName);
            $hasManyFunctionName = $this->getPluralFunctionName($hasManyModel);

            // @toconsider: if it's a table with only 2 columns, and they are both the FK
            // then it's just a pure pivot table. We might not want to add a relation for that.

            $relation = new HasManyRelation($hasManyFunctionName, $hasManyModel, $field, $references);

            /** @var TableRelations $tableRelations */
            $tableRelations = $relationsByTable[$fkTable];
            $tableRelations->addHasManyRelation($relation);
        }
        if(in_array($tableName, $tableNames)) {
            $belongsToModel = $this->generateModelNameFromTableName($fkTable);
            $belongsToFunctionName = $this->getSingularFunctionName($belongsToModel);
            $relation = new BelongsToRelation($belongsToFunctionName, $belongsToModel, $field, $references);

            /** @var TableRelations $tableRelations */
            $tableRelations = $relationsByTable[$tableName];
            $tableRelations->addBelongsToRelation($relation);
        }
    }

    private function addOneToOneRules(string $tableName, ForeignKey $fk, array &$relationsByTable)
    {
        //$table belongsTo $FK
        //$FK hasOne $table

        $tableNames = $this->schema->getTableNames()->toArray();

        $fkTable = $fk->getForeignTableName();
        $localColumns = $fk->getLocalColumns();
        $foreignColumns = $fk->getForeignColumns();

        if(count($localColumns) > 1) {
            throw new \Exception('Composite local keys are not supported');
        }

        if(count($foreignColumns) > 1) {
            throw new \Exception('Composite foreign columns are not supported');
        }

        // switched ????
        $field = $localColumns[0];
        $references = $foreignColumns[0];

        if(in_array($fkTable, $tableNames)) {
            $modelName = $this->generateModelNameFromTableName($tableName);
            $functionName = $this->getSingularFunctionName($modelName);

            $relation = new HasOneRelation($functionName, $modelName, $field, $references);

            /** @var TableRelations $tableRelations */
            $tableRelations = $relationsByTable[$fkTable];
            $tableRelations->addHasOneRelation($relation);
        }
        if(in_array($tableName, $tableNames)) {
            $belongsToModel = $this->generateModelNameFromTableName($fkTable);
            $belongsToFunctionName = $this->getSingularFunctionName($belongsToModel);
            $relation = new BelongsToRelation($belongsToFunctionName, $belongsToModel, $field, $references);

            /** @var TableRelations $tableRelations */
            $tableRelations = $relationsByTable[$tableName];
            $tableRelations->addBelongsToRelation($relation);
        }
    }

    /**
     * @param string $tableName
     * @param TableRelations[] $rules
     * @return void
     */
    private function addManyToManyRelations(string $tableName, array &$relationsByTable)
    {
        $tableNames = $this->schema->getTableNames()->toArray();
        $foreign = $this->getForeignKeysForTable($tableName);

        //$FK1 belongsToMany $FK2
        //$FK2 belongsToMany $FK1

        /** @var ForeignKey $fk1 */
        $fk1 = $foreign[0];

        /** @var ForeignKey $fk2 */
        $fk2 = $foreign[1];

        if((count($fk1->getLocalColumns()) > 1) || (count($fk2->getLocalColumns()) > 1)) {
            throw new \Exception('Composite primary keys are not supported');
        }

        $fk1Table = $fk1->getForeignTableName();
        $fk1Field = $fk1->getLocalColumns()[0];

        $fk2Table = $fk2->getForeignTableName();
        $fk2Field = $fk2->getLocalColumns()[0];

        if (in_array($fk1Table, $tableNames)) {
            $belongsToManyModel = $this->generateModelNameFromTableName($fk2Table);
            $belongsToManyFunctionName = $this->getPluralFunctionName($belongsToManyModel);
            $through = $tableName;

            $relation = new BelongsToManyRelation($belongsToManyFunctionName, $belongsToManyModel, $through, $fk1Field, $fk2Field);

            /** @var TableRelations $tableRelations */
            $tableRelations = $relationsByTable[$fk1Table];
            $tableRelations->addBelongsToManyRelation($relation);
        }
        if (in_array($fk2Table, $tableNames)) {
            $belongsToManyModel = $this->generateModelNameFromTableName($fk1Table);
            $belongsToManyFunctionName = $this->getPluralFunctionName($belongsToManyModel);

            $through = $tableName;
            $relation = new BelongsToManyRelation($belongsToManyFunctionName, $belongsToManyModel, $through, $fk2Field, $fk1Field);

            /** @var TableRelations $tableRelations */
            $tableRelations = $relationsByTable[$fk2Table];
            $tableRelations->addBelongsToManyRelation($relation);
        }
    }

    //if FK is also a primary key, and there is only one primary key, we know this will be a one to one relationship
    private function isOneToOne(ForeignKey $fk, Collection $primary)
    {
        if (count($primary) !== 1) {
            return false;
        }

        /** @var Index $prim */
        foreach ($primary as $prim) {

            $primaryColumns = $prim->getColumns();
            $foreignColumns = $fk->getLocalColumns();

            if(count($primaryColumns) > 1) {
                throw new \Exception('Composite primary keys are not supported');
            }

            if(count($foreignColumns) > 1) {
                throw new \Exception('Composite foreign keys are not supported');
            }

            $primaryKeyColumn = $primaryColumns[0];
            $foreignKeyColumn = $foreignColumns[0];

            if ($primaryKeyColumn === $foreignKeyColumn) {
                // dd($primaryKeyColumn, $foreignKeyColumn, $prim, $fk);
                // dd($fk, $primary);
                return true;
            }
        }
    }

    // does this table have exactly two foreign keys that are
    //  - either both primary,
    //  - or neither are primary
    // ... and no other tables in the database refer to this table?
    // then it's cleary a pivot / many-to-many table!
    private function isManyToManyTable(string $tableName): bool
    {
        $tableNames = $this->schema->getTableNames();

        $foreignKeys = $this->getForeignKeysForTable($tableName);
        $primaryKeys = $this->getPrimaryKeysForTable($tableName);

        //ensure we only have two foreign keys
        if (count($foreignKeys) === 2) {
            //ensure our foreign keys are not also defined as primary keys
            $primaryKeyCountThatAreAlsoForeignKeys = 0;

            foreach ($foreignKeys as $foreignKey) {
                foreach ($primaryKeys as $primaryKey) {
                    if ($primaryKey->getName() === $foreignKey->getName()) {
                        ++$primaryKeyCountThatAreAlsoForeignKeys;
                    }
                }
            }

            if ($primaryKeyCountThatAreAlsoForeignKeys === 1) {
                // one of the keys foreign keys was also a primary key
                // this is not a many to many. (many to many is only possible when both or none of the foreign keys are also primary)
                return false;
            }

            // ensure no other tables refer to this one
            foreach ($tableNames as $compareTable) {
                if ($tableName !== $compareTable) {
                    $compareFK = $this->getForeignKeysForTable($compareTable);

                    /** @var ForeignKey $compareForeignKey */
                    foreach ($compareFK as $compareForeignKey) {
                        if ($compareForeignKey->getForeignTableName() === $tableName) {
                            return false;
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $tableName
     * @return Collection
     */
    private function getForeignKeysForTable(string $tableName): Collection
    {
        return $this->schema->getTableForeignKeys($tableName);
    }

    /**
     * @param string $tableName
     * @return Collection<Index>
     */
    private function getPrimaryKeysForTable(string $tableName): Collection
    {
        $table = $this->schema->getTable($tableName);
        $primaryKeys = $table->getIndexes()->filter(function($index) {
            return $index->getType() == IndexType::PRIMARY();
        });

        return $primaryKeys;
    }
}
