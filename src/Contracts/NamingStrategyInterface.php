<?php

namespace Pepijnolivier\EloquentModelGenerator\Contracts;

use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Relations\SchemaRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\BelongsToManyRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\BelongsToRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\HasManyRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\HasOneRelationVO;

interface NamingStrategyInterface
{
    public static function generateModelNameFromTableName(Schema $schema, SchemaRelations $relations, string $tableName): string;

    public static function generateHasOneFunctionName(HasOneRelationVO $vo): string;

    public static function generateHasManyFunctionName(HasManyRelationVO $vo): string;

    public static function generateBelongsToFunctionName(BelongsToRelationVO $vo): string;

    public static function generateBelongsToManyFunctionName(BelongsToManyRelationVO $vo): string;
}
