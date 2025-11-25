<?php

namespace Pepijnolivier\EloquentModelGenerator\NamingStrategies;

use Illuminate\Support\Str;
use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Contracts\NamingStrategyInterface;
use Pepijnolivier\EloquentModelGenerator\Relations\SchemaRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\BelongsToManyRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\BelongsToRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\HasManyRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\HasOneRelationVO;

class LegacyNamingStrategy implements NamingStrategyInterface
{
    public static function generateModelNameFromTableName(Schema $schema, SchemaRelations $relations, string $tableName): string
    {
        return ucfirst(Str::camel(Str::singular($tableName)));
    }

    public static function generateHasOneFunctionName(HasOneRelationVO $vo): string
    {
        $hasOneModel = self::generateModelNameFromTableName(
            $vo->getSchema(),
            $vo->getRelations(),
            $vo->getForeignKey()->getTableName()
        );

        return self::getSingularFunctionName($hasOneModel);
    }

    public static function generateHasManyFunctionName(HasManyRelationVO $vo): string
    {
        $hasManyModel = self::generateModelNameFromTableName(
            $vo->getSchema(),
            $vo->getRelations(),
            $vo->getForeignKey()->getTableName()
        );

        return self::getPluralFunctionName($hasManyModel);
    }

    public static function generateBelongsToFunctionName(BelongsToRelationVO $vo): string
    {
        $belongsToModel = self::generateModelNameFromTableName(
            $vo->getSchema(),
            $vo->getRelations(),
            $vo->getForeignKey()->getForeignTableName()
        );

        return self::getSingularFunctionName($belongsToModel);
    }

    public static function generateBelongsToManyFunctionName(BelongsToManyRelationVO $vo): string
    {
        $belongsToManyModel = self::generateModelNameFromTableName(
            $vo->getSchema(),
            $vo->getRelations(),
            $vo->getFk2()->getForeignTableName()
        );

        return self::getPluralFunctionName($belongsToManyModel);
    }

    protected static function getPluralFunctionName(string $modelName): string
    {
        $modelName = lcfirst($modelName);
        return Str::plural($modelName);
    }

    protected static function getSingularFunctionName(string $modelName): string
    {
        $modelName = lcfirst($modelName);
        return Str::singular($modelName);
    }
}
