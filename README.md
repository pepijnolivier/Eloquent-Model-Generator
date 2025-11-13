# Eloquent Model Generator


[![Latest Version on Packagist](https://img.shields.io/packagist/v/pepijnolivier/eloquent-model-generator.svg?style=flat-square)](https://packagist.org/packages/pepijnolivier/eloquent-model-generator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pepijnolivier/eloquent-model-generator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pepijnolivier/eloquent-model-generator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pepijnolivier/eloquent-model-generator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pepijnolivier/eloquent-model-generator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/pepijnolivier/eloquent-model-generator.svg?style=flat-square)](https://packagist.org/packages/pepijnolivier/eloquent-model-generator)



This Laravel package will generate models with their appropriate Eloquent relations based on an existing database schema.

For automatically generating database migrations for your schema, see [kitloong/laravel-migrations-generator](https://github.com/kitloong/laravel-migrations-generator)

## Requirements

- PHP 8.4+
- Laravel 12+

## Installation

You can install the package via composer:

```bash
composer require --dev pepijnolivier/eloquent-model-generator
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="eloquent-model-generator-config"
```

This is the contents of the published config file:

```php
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
];

```


## Usage

```php
php artisan generate:models
```

## Testing

```bash
composer test
```

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please see [SECURITY](SECURITY.md) for details.

## Credits

- [Pepijn Olivier](https://github.com/pepijnolivier)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
