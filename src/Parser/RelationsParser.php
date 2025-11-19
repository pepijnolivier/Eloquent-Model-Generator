<?php

namespace Pepijnolivier\EloquentModelGenerator\Parser;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use KitLoong\MigrationsGenerator\Enum\Migrations\Method\IndexType;
use KitLoong\MigrationsGenerator\Schema\Models\ForeignKey;
use KitLoong\MigrationsGenerator\Schema\Models\Index;
use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Contracts\NamingStrategyInterface;
use Pepijnolivier\EloquentModelGenerator\Factories\Relations\HasOneRelationFactory;
use Pepijnolivier\EloquentModelGenerator\NamingStrategies\LegacyNamingStrategy;
// use Pepijnolivier\EloquentModelGenerator\NamingStrategies\ValueObjects\HasOneRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\SchemaRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\TableRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToManyRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasManyRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasOneRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\BelongsToRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\HasManyRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\HasOneRelationVO;
use Pepijnolivier\EloquentModelGenerator\Traits\HelperTrait;
use Pepijnolivier\EloquentModelGenerator\Traits\OutputsToConsole;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class RelationsParser
{
    use OutputsToConsole;
    use HelperTrait;

    /** @var Schema $schema */
    protected Schema $schema;

    /** @var String[] $tables */
    protected array $tables;

    /** @var SchemaRelations $schemaRelations */
    protected SchemaRelations $schemaRelations;

    protected NamingStrategyInterface $namingStrategy;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
        $this->namingStrategy = $this->getNamingStrategy();

        $this->init();

        $this->parse();
    }


    protected function init() {

        $this->comment("[RelationsParser] initializing");

        $this->tables = $this->schema->getTableNames()->toArray();
        $this->schemaRelations = new SchemaRelations($this->tables);
    }

    public function getRelationsForTable(string $tableName): TableRelations
    {
        return $this->schemaRelations->getTableRelations($tableName);
    }


    public function getSchema(): SchemaRelations
    {
        return $this->schemaRelations;
    }

    private function parse()
    {

        $this->comment("[RelationsParser] parsing");

        $count = count($this->tables);
        $this->usingProgressbar($count, function (ProgressBar $progressBar) {
            /** @var string $tableName */
            foreach ($this->tables as $index => $tableName) {
                // for some reason, some loop iterations are very, very slow
                $this->parseTable($tableName, $progressBar);
            }
        });
    }

    protected function parseTable(string $tableName, ProgressBar $progressBar)
    {
        $table = $this->schema->getTable($tableName);
        $foreign = $this->getForeignKeysForTable($tableName);
        $primary = $this->getPrimaryKeysForTable($tableName);

        // @improvement we should probably pass in the table everywhere, instead of the table name...
        $isManyToMany = $this->isManyToManyTable($tableName);

        if ($isManyToMany === true) {
            $relationsByTable = $this->schemaRelations->getSchemaRelations();
            $this->addManyToManyRelations($tableName, $relationsByTable);
        }

        foreach ($foreign as $fk) {
            $isOneToOne = $this->isOneToOne($fk, $primary);

            if ($isOneToOne) {
                $this->addOneToOneRules($tableName, $fk);
            } else {
                $this->addOneToManyRules($tableName, $fk);
            }
        }

        $progressBar->advance();
    }

    /**
     * @var TableRelations[] $relationsByTable
     */
    private function addOneToManyRules(string $tableName, ForeignKey $fk)
    {
        // $tableName belongs to $FK
        // FK hasMany $table

        $tableNames = $this->schema->getTableNames()->toArray();
        $fkTable = $fk->getForeignTableName();

        // validate: ensure there's exactly 1 local and 1 foreign column
        $isComposite = $this->hasCompositeLocalOrForeignColumn($fk);
        if($isComposite) {
            return;
        }
        if(in_array($fkTable, $tableNames)) {
            $vo = new HasManyRelationVO(
                $this->schema,
                $this->schemaRelations,
                $this->namingStrategy,
                $fk
            );
            $relation = $vo->getRelation();
            $this->schemaRelations->addHasManyRelation($fkTable, $relation);
        }
        if(in_array($tableName, $tableNames)) {
            $vo = new BelongsToRelationVO(
                $this->schema,
                $this->schemaRelations,
                $this->namingStrategy,
                $fk
            );

            $relation = $vo->getRelation();
            $this->schemaRelations->addBelongsToRelation($tableName, $relation);
        }
    }

    private function hasCompositeLocalOrForeignColumn(ForeignKey $fk): bool {

        $fkLocalColumns = $fk->getLocalColumns();
        $fkForeignColumns = $fk->getForeignColumns();

        if(count($fkLocalColumns) > 1) {
            // throw new \Exception('Composite local keys are not supported');
            return true;
        }

        if(count($fkForeignColumns) > 1) {
            // throw new \Exception('Composite foreign columns are not supported');
            return true;
        }

        return false;
    }

    private function addOneToOneRules(string $tableName, ForeignKey $fk)
    {
        // $tableName belongsTo $FK
        // $FK hasOne $table

        $tableNames = $this->schema->getTableNames()->toArray();
        $fkTable = $fk->getForeignTableName();

        // validate: ensure there's exactly 1 local and 1 foreign column
        $isComposite = $this->hasCompositeLocalOrForeignColumn($fk);
        if($isComposite) {
            return;
        }

        $fkLocalColumn = $fk->getLocalColumns()[0];
        $fkForeignColumn = $fk->getForeignColumns()[0];

        if (in_array($fkTable, $tableNames)) {
            $vo = new HasOneRelationVO(
                $this->schema,
                $this->schemaRelations,
                $this->namingStrategy,
                $fk
            );

            $relation = $vo->getRelation();
            $this->schemaRelations->addHasOneRelation($fkTable, $relation);
        }
        if(in_array($tableName, $tableNames)) {

            $vo = new BelongsToRelationVO(
                $this->schema,
                $this->schemaRelations,
                $this->namingStrategy,
                $fk
            );

            $relation = $vo->getRelation();


             // $relation = BelongsToRelation::fromTable($fkTable, $fkLocalColumn, $fkForeignColumn);
             $this->schemaRelations->addBelongsToRelation($tableName, $relation);

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
            // throw new \Exception('Composite primary keys are not supported');
            return;
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
    private function isOneToOne(ForeignKey $fk, Collection $primary): bool
    {
        if (count($primary) !== 1) {
            return false;
        }

        /** @var Index $prim */
        foreach ($primary as $prim) {

            $primaryColumns = $prim->getColumns();
            $foreignColumns = $fk->getLocalColumns();

            if(count($primaryColumns) > 1) {
                // throw new \Exception('Composite primary keys are not supported');
                return false;
            }

            if(count($foreignColumns) > 1) {
                // throw new \Exception('Composite foreign keys are not supported');
                return false;
            }

            $primaryKeyColumn = $primaryColumns[0];
            $foreignKeyColumn = $foreignColumns[0];

            if ($primaryKeyColumn === $foreignKeyColumn) {
                return true;
            }
        }

        return false;
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
     * @return Collection<ForeignKey>
     */
    private function getForeignKeysForTable(string $tableName): Collection
    {
        return $this->schema->getForeignKeys($tableName);
    }

    /**
     * @param string $tableName
     * @return Collection<Index>
     */
    private function getPrimaryKeysForTable(string $tableName): Collection
    {
        $table = $this->schema->getTable($tableName);
        $primaryKeys = $table->getIndexes()->filter(function($index) {
            return $index->getType() == IndexType::PRIMARY;
        });

        return $primaryKeys;
    }

    private function getNamingStrategy(): NamingStrategyInterface
    {
        $namingStrategyClass = config(
            'eloquent_model_generator.naming_strategy',
            LegacyNamingStrategy::class
        );

        // ensure that this class implements NamingStrategyInterface
        if (!is_subclass_of($namingStrategyClass, NamingStrategyInterface::class)) {
            throw new \InvalidArgumentException("Naming strategy class must implement NamingStrategyInterface");
        }

        return app($namingStrategyClass);
    }
}
