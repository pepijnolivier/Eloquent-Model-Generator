# Eloquent-Model-Generator for Laravel 5
Auto-generates all Eloquent models from the database in a Laravel 5 project.
This will also add all relation functions to your generated models (belongsTo, belongsToMany, hasMany, hasOne).

> I'm also creating a ServiceProvider that will auto-generate basic CRUD functionality for these models

##Installation

Add the following packages to your `composer.json`

```
"require-dev": {
    "xethron/migrations-generator": "dev-l5",
    "way/generators": "dev-feature/laravel-five-stable",
    "user11001/eloquent-model-generator": "dev-master"
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

`php artisan models:generate`

