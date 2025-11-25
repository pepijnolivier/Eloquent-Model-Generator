<?php

use Illuminate\Database\Eloquent\Model;

return [
    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | The default namespace for generated models.
    |
    */
    'model_namespace' => 'App\Models\Generated',
    'trait_namespace' => 'App\Models\Generated\Relations',

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | Path where the models will be created.
    |
    */
    'model_path' => 'app/Models/Generated',
    'trait_path' => 'app/Models/Generated/Relations',

    /*
    |--------------------------------------------------------------------------
    | Extend Model
    |--------------------------------------------------------------------------
    |
    | Extend the base model.
    |
    */
    'extend' => Model::class,



    // 'naming_strategy' => \Pepijnolivier\EloquentModelGenerator\NamingStrategies\LegacyNamingStrategy::class,
    'naming_strategy' => \Pepijnolivier\EloquentModelGenerator\NamingStrategies\ColumnBasedNamingStrategy::class,
];
