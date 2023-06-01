<?php

namespace Pepijnolivier\EloquentModelGenerator\Relations;

use Illuminate\Support\Arr;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToManyRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\BelongsToRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasManyRelation;
use Pepijnolivier\EloquentModelGenerator\Relations\Types\HasOneRelation;

class TableRelations
{
    protected array $relations = [];

    public function __construct(
        protected string $tableName
    ) {

    }

    public function hasRelationships()
    {
        return !empty($this->relations);
    }

    public function addBelongsToRelation(BelongsToRelation $relation)
    {
        $this->relations['belongsTo'][] = $relation;
    }

    public function addHasOneRelation(HasOneRelation $relation)
    {
        $this->relations['hasOne'][] = $relation;
    }

    public function addHasManyRelation(HasManyRelation $relation)
    {
        $this->relations['hasMany'][] = $relation;
    }

    public function addBelongsToManyRelation(BelongsToManyRelation $relation)
    {
        $this->relations['belongsToMany'][] = $relation;
    }

    /**
     * @return BelongsToRelation[]
     */
    public function getBelongsToRelations(): array
    {
        return Arr::get($this->relations, 'belongsTo', []);
    }

    /**
     * @return HasOneRelation[]
     */
    public function getHasOneRelations(): array
    {
        return Arr::get($this->relations, 'hasOne', []);
    }

    /**
     * @return HasManyRelation[]
     */
    public function getHasManyRelations(): array
    {
        return Arr::get($this->relations, 'hasMany', []);
    }

    /**
     * @return BelongsToManyRelation[]
     */
    public function getBelongsToManyRelations(): array
    {
        return Arr::get($this->relations, 'belongsToMany', []);
    }


    public function getTableName(): string
    {
        return $this->tableName;
    }
}
