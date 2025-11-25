<?php

namespace Pepijnolivier\EloquentModelGenerator\NamingStrategies;

use Illuminate\Support\Str;
use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Contracts\NamingStrategyInterface;
use Pepijnolivier\EloquentModelGenerator\Relations\SchemaRelations;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\PivotRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\BelongsToRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\HasManyRelationVO;
use Pepijnolivier\EloquentModelGenerator\Relations\ValueObjects\HasOneRelationVO;

class ColumnBasedNamingStrategy implements NamingStrategyInterface
{

    public static function generateModelNameFromTableName(Schema $schema, SchemaRelations $relations, string $tableName): string
    {
        return LegacyNamingStrategy::generateModelNameFromTableName($schema, $relations, $tableName);
    }

    public static function generateHasOneFunctionName(HasOneRelationVO $vo): string
    {
        return LegacyNamingStrategy::generateHasOneFunctionName($vo);
    }

    /**
     * Generates hasMany function names based on the foreign key column.
     *
     * Examples:
     * - posts.author_id pointing to "users" table
     *   → relation on "users" table will be "authoredPosts" (descriptive when column indicates a role)
     *
     * - users.nationality_id pointing to "nationalities" table
     *   → relation on "nationalities" table will be "users" (simple when column matches model name)
     */
    public static function generateHasManyFunctionName(HasManyRelationVO $vo): string
    {
        $localColumn = $vo->getFkLocalColumn();

        if(Str::endsWith($localColumn, '_id')) {
            $withoutIdSuffix = Str::remove('_id', $localColumn);
            $currentModelTable = $vo->getForeignKey()->getForeignTableName();
            $currentModelSingular = Str::singular($currentModelTable);
            $relatedTable = $vo->getForeignKey()->getTableName();

            if($withoutIdSuffix === $currentModelSingular) {
                return Str::camel(Str::plural(Str::singular($relatedTable)));
            }

            $prefix = Str::camel(self::pastParticiple($withoutIdSuffix));
            $related = Str::studly(Str::plural(Str::singular($relatedTable)));

            return lcfirst($prefix . $related);
        }

        return LegacyNamingStrategy::generateHasManyFunctionName($vo);
    }

    /**
     * Generates belongsTo function names by removing the _id suffix from the foreign key column.
     *
     * Example: author_id → "author"
     */
    public static function generateBelongsToFunctionName(BelongsToRelationVO $vo): string
    {
        $localColumn = $vo->getFkLocalColumn();

        if(Str::endsWith($localColumn, '_id')) {
            $withoutIdSuffix = Str::remove('_id', $localColumn);
            return Str::camel($withoutIdSuffix);
        }

        return LegacyNamingStrategy::generateBelongsToFunctionName($vo);
    }

    /**
     * Generates belongsToMany function names that reflect non-standard pivot tables.
     *
     * For standard pivot tables (e.g., post_user), falls back to legacy naming.
     * For non-standard pivot tables, prefixes with the pivot table name in past participle form.
     *
     * Example: users → posts via comments → "commentedPosts"
     */
    public static function generateBelongsToManyFunctionName(PivotRelationVO $vo): string
    {
        $fk1 = $vo->getFk1();
        $fk2 = $vo->getFk2();

        $fk1Table = $fk1->getTableName();
        $fk2Table = $fk2->getForeignTableName();

        $isStandardPivot = self::isStandardPivotTable(
            pivotTable: $fk1Table,
            modelTable: $fk2Table,
            relatedTable: $fk1->getForeignTableName()
        );

        if($isStandardPivot) {
            return LegacyNamingStrategy::generateBelongsToManyFunctionName($vo);
        }

        $pivotTableName = $fk1Table;
        $relatedTable = $fk2->getForeignTableName();
        $pivotSingular = Str::singular($pivotTableName);
        $prefix = Str::camel(self::pastParticiple($pivotSingular));
        $related = Str::studly(Str::plural(Str::singular($relatedTable)));

        return lcfirst($prefix . $related);
    }

    /**
     * Converts a word to past participle form for relation naming.
     *
     * This doesn't try to be grammatically perfect - just predictable.
     * Works well for common patterns: like → liked, reply → replied, comment → commented.
     *
     * @param string $word The singular form of the word
     */
    protected static function pastParticiple(string $word): string
    {
        if (preg_match('/e$/', $word)) {
            return $word.'d';
        }

        if (preg_match('/y$/', $word)) {
            return preg_replace('/y$/', 'ied', $word);
        }

        return $word.'ed';
    }

    /**
     * Checks if a pivot table follows Laravel's standard naming convention.
     *
     * Standard pivot tables are named as the concatenation of related tables
     * usually in alphabetical order (e.g., post_user for posts and users).
     */
    protected static function isStandardPivotTable(string $pivotTable, string $modelTable, string $relatedTable): bool
    {
        $singularModelTable = Str::singular($modelTable);
        $singularRelatedTable = Str::singular($relatedTable);

        $candidates = [
            $singularModelTable . '_' . $singularRelatedTable,
            $singularRelatedTable . '_' . $singularModelTable,
        ];

        return in_array($pivotTable, $candidates);
    }
}
