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

    public static function generateHasManyFunctionName(HasManyRelationVO $vo): string
    {
        /**
         * read the foreign key local column
         * eg if we're currently processing the table "users" and the foreign local column is posts.author_id
         *
         * then a good hasMany function name would be "authoredPosts"
         * This leaves "posts" open for other relations that might exist between users and posts
         */

        $localColumn = $vo->getFkLocalColumn();

        if(Str::endsWith($localColumn, '_id')) {
            // Extract the prefix from the column name (e.g., "author_id" → "author")
            $withoutIdSuffix = Str::remove('_id', $localColumn);

            // Get current model's table name to check if this is a standard reference
            $currentModelTable = $vo->getForeignKey()->getForeignTableName();
            $currentModelSingular = Str::singular($currentModelTable);

            // Get the related table name (e.g., "posts")
            $relatedTable = $vo->getForeignKey()->getTableName();

            // If the column base matches the current model's singular name,
            // use simple plural to avoid verbosity (e.g., nationality.nationality_id → "users", not "nationalitiedUsers")
            if($withoutIdSuffix === $currentModelSingular) {
                return Str::camel(Str::plural(Str::singular($relatedTable)));
            }

            // Otherwise, use past participle prefix for more descriptive names
            // (e.g., posts.author_id → "authoredPosts")
            $prefix = Str::camel(self::pastParticiple($withoutIdSuffix));
            $related = Str::studly(Str::plural(Str::singular($relatedTable)));

            return lcfirst($prefix . $related);
        }

        return LegacyNamingStrategy::generateHasManyFunctionName($vo);
    }

    public static function generateBelongsToFunctionName(BelongsToRelationVO $vo): string
    {
        $localColumn = $vo->getFkLocalColumn();

        if(Str::endsWith($localColumn, '_id')) {
            // camelcase
            $withoutIdSuffix = Str::remove('_id', $localColumn);
            return Str::camel($withoutIdSuffix);
        }

        return LegacyNamingStrategy::generateBelongsToFunctionName($vo);
    }

    public static function generateBelongsToManyFunctionName(PivotRelationVO $vo): string
    {
        // eg for table 'users', we have a belongsToMany 'posts' via 'comments' table
        // so the legacy name for this relation would be 'posts'
        // but we want to reflect the pivot table 'comments' in the name
        // so a better name would be commentedPosts

        // first, we should detect the name of the pivot table.
        // if it's a standard pivot table, it will be the concatenation of the two related tables in alphabetical order
        // if so, we should fallback to the legacy naming strategy

        $fk1 = $vo->getFk1(); // FK to the current model
        $fk2 = $vo->getFk2(); // FK to the related model

        $fk1Table = $fk1->getTableName();
        $fk2Table = $fk2->getForeignTableName();

        // dd($fk1Table, $fk2Table, $fk1->getForeignTableName());

        $isStandardPivot = self::isStandardPivotTable(
            pivotTable: $fk1Table,
            modelTable: $fk2Table,
            relatedTable: $fk1->getForeignTableName()
        );

        if($isStandardPivot) {
            return LegacyNamingStrategy::generateBelongsToManyFunctionName($vo);
        }



        // if it's not a standard pivot table, eg "users > comments > posts"
        // and we are processing the users table
        // then we can use the pivot table name 'comments' as a prefix for the relation
        // so the relation name becomes 'commentedPosts'


        $pivotTableName = $fk1Table; // eg 'comments'
        $relatedTable = $fk2->getForeignTableName();

        $pivotSingular = Str::singular($pivotTableName);   // 'comments' → 'comment'

        $prefix = Str::camel(self::pastParticiple($pivotSingular));

        $related = Str::studly(Str::plural(Str::singular($relatedTable))); // 'Posts'

        return lcfirst($prefix . $related);  // 'commentedPosts'
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


    /**
     * This doesn’t try to be grammatically perfect—just predictable.
     * For most pivot tables (“comments”, “likes”, “follows”, “views”, “favorites”), this works well.
     *
     * important: pass the singular form of the word
     */
    protected static function pastParticiple(string $word): string
    {
        // handle simple cases
        if (preg_match('/e$/', $word)) {
            return $word.'d';  // like → liked, name → named
        }

        if (preg_match('/y$/', $word)) {
            return preg_replace('/y$/', 'ied', $word); // reply → replied
        }

        // default: just add 'ed'
        return $word.'ed';
    }

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
