<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations;

use Illuminate\Support\Arr;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToManyRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasManyRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasOneRelation;

class SchemaRelations
{
    protected array $relations = [];

    /**
     * @var String[] $tableNames
     */
    public function __construct(
        protected array $tableNames
    ) {
        $this->init();
    }

    protected function init() {
        foreach($this->tableNames as $tableName) {
            $this->relations[$tableName] = new TableRelations($tableName);
        }
    }

    public function getTableRelations(string $table): TableRelations {
        $this->throwIfInValidTable($table);
        return $this->relations[$table];
    }

    public function addBelongsToRelation(string $table, BelongsToRelation $relation)
    {
        $this->throwIfInValidTable($table);
        $this->relations[$table]->addBelongsToRelation($relation);
    }

    public function addHasOneRelation(string $table, HasOneRelation $relation)
    {
        $this->throwIfInValidTable($table);
        $this->relations[$table]->addHasOneRelation($relation);
    }

    public function addHasManyRelation(string $table, HasManyRelation $relation)
    {
        $this->throwIfInValidTable($table);
        $this->relations[$table]->addHasManyRelation($relation);
    }

    public function addBelongsToManyRelation(string $table, BelongsToManyRelation $relation)
    {
        $this->throwIfInValidTable($table);
        $this->relations[$table]->addBelongsToManyRelation($relation);
    }

    protected function throwIfInvalidTable($table) {
        if(!isset($this->relations[$table])) {
            throw new \Exception("Unknown table: '$table'");
        }
    }
}
