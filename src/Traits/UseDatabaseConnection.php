<?php

namespace Pepijnolivier\EloquentModelGenerator\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

trait UseDatabaseConnection {

    protected static function usingDatabaseConnection(string $connection, callable $fn)
    {
        self::validateConnection($connection);
        $originalConnection = config('database.default');

        try {
            Config::set('database.connection', $connection);

            $fn();
        } finally {
            Config::set('database.connection', $originalConnection);
        }
    }


    /**
     * Validate that the specified connection exists in the Laravel configuration.
     *
     * @param string $connection
     * @throws \RuntimeException
     */
    protected static function validateConnection(string $connection): void
    {
        $connections = config('database.connections');

        if (!isset($connections[$connection])) {
            throw new \RuntimeException("Database connection '{$connection}' not found in configuration.");
        }
    }

}
