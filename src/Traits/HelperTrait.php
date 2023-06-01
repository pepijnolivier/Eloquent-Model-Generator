<?php

namespace Pepijnolivier\EloquentModelGenerator\Traits;

use Illuminate\Support\Str;

trait HelperTrait {

    protected function getPluralFunctionName($modelName)
    {
        $modelName = lcfirst($modelName);
        return Str::plural($modelName);
    }

    protected function getSingularFunctionName($modelName)
    {
        $modelName = lcfirst($modelName);
        return Str::singular($modelName);
    }

    protected function generateModelNameFromTableName($table)
    {
        return ucfirst(Str::camel(Str::singular($table)));
    }
}
