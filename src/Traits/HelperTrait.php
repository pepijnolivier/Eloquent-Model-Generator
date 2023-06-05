<?php

namespace Pepijnolivier\EloquentModelGenerator\Traits;

use Illuminate\Support\Str;

trait HelperTrait {

    protected static function getPluralFunctionName($modelName)
    {
        $modelName = lcfirst($modelName);
        return Str::plural($modelName);
    }

    protected static function getSingularFunctionName($modelName)
    {
        $modelName = lcfirst($modelName);
        return Str::singular($modelName);
    }

    protected static function generateModelNameFromTableName($table)
    {
        return ucfirst(Str::camel(Str::singular($table)));
    }
}
