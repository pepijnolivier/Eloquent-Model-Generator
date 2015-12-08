# Eloquent-Model-Generator for Laravel 5
Auto-generates all Eloquent models from the database in a Laravel 5 project.
This will also add all relation functions to your generated models (belongsTo, belongsToMany, hasMany, hasOne).

> I'm also creating a ServiceProvider that will auto-generate basic CRUD functionality for these models. For now, you can use [Laravel Administrator](https://github.com/FrozenNode/Laravel-Administrator) or [Dick Crud](https://github.com/tabacitu/crud) for this task. You should also look at [Laravel API Generator](https://github.com/mitulgolakiya/laravel-api-generator)

##Installation

Add the following packages to your `composer.json`

```
"require-dev": {
    "xethron/migrations-generator": "dev-l5",
    "way/generators": "dev-feature/laravel-five-stable",
    "user11001/eloquent-model-generator": "~1.0"
}
```


You also need to point to the fork of the way/generators repo. See [Xethron/migrations-generator](https://github.com/Xethron/migrations-generator) for more info about this.

```
"repositories": [
    {
        "type": "git",
        "url": "git@github.com:jamisonvalenta/Laravel-4-Generators.git"
    }
]
```


Next, run `composer update`


Next, add the following service providers to your `config/app.php`
```
'Way\Generators\GeneratorsServiceProvider',
'Xethron\MigrationsGenerator\MigrationsGeneratorServiceProvider',
'User11001\EloquentModelGenerator\EloquentModelGeneratorProvider',
```

Lastly, make sure your `.env` file has correct database information

##Usage

**Default: generate all models into the default folder**
> `php artisan models:generate`

**Specify path where to generate to**
> `php artisan models:generate --path="app/Models"`

**Specify the namespace of the models**
> `php artisan models:generate --namespace="User11001/Models"`

**Overwrite existing models**
> `php artisan models:generate --overwrite"`


##Example
See mysql workbench schema `example.mwb` and `example.sql` in `/example`

##Contributors and Pull Requests
Please contribute and help enhance this package. All pull requests will be revised quickly.
Please use the development branch for pull requests.
