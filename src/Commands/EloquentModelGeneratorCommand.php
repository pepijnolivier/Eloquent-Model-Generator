<?php

namespace Pepijnolivier\EloquentModelGenerator\Commands;

use Exception;
use RuntimeException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use KitLoong\MigrationsGenerator\Enum\Driver;
use KitLoong\MigrationsGenerator\Schema\MySQLSchema;
use KitLoong\MigrationsGenerator\Schema\PgSQLSchema;
use KitLoong\MigrationsGenerator\Schema\Schema;
use KitLoong\MigrationsGenerator\Schema\SQLiteSchema;
use KitLoong\MigrationsGenerator\Schema\SQLSrvSchema;
use Pepijnolivier\EloquentModelGenerator\Generators\Generator;
use Pepijnolivier\EloquentModelGenerator\Parser\RelationsParser;
use Pepijnolivier\EloquentModelGenerator\Traits\UseDatabaseConnection;

class EloquentModelGeneratorCommand extends Command
{
    use UseDatabaseConnection;

    public $signature = 'generate:models {--connection= : The database connection to use}';
    public $description = 'Generate models from the database with relations.';

    private Schema $schema;
    private RelationsParser $parser;
    private Generator $generator;

    public function handle(): int
    {
        $connection = $this->option('connection') ?? config('database.default');
        self::validateConnection($connection);

        self::usingDatabaseConnection($connection, function() use ($connection) {
            $this->schema = $this->getSchema();
            $this->parser = new RelationsParser($this->schema);
            $this->generator = new Generator($connection, $this->schema, $this->parser);

            $this->generate();
        });

        $this->info('All done');
        return self::SUCCESS;
    }

    private function generate()
    {
        $tableNames = $this->schema->getTableNames()->toArray();
        foreach ($tableNames as $table) {
            try {
                $this->generator->handle($table);
            } catch(\Exception $e) {
                Log::error($e->getMessage());
                $this->error("\nFailed to generate model for table $table");
            }
        }
    }

    /**
     * Get DB schema
     *
     * @throws \Exception
     */
    protected function getSchema(): Schema
    {
        $driver = DB::getDriverName();

        if (!$driver) {
            throw new Exception('Failed to find database driver.');
        }

        switch ($driver) {
            case Driver::MYSQL->value:
                return $this->schema = app(MySQLSchema::class);

            case Driver::PGSQL->value:
                return $this->schema = app(PgSQLSchema::class);

            case Driver::SQLITE->value:
                return $this->schema = app(SQLiteSchema::class);

            case Driver::SQLSRV->value:
                return $this->schema = app(SQLSrvSchema::class);

            default:
                throw new Exception('The database driver in use is not supported.');
        }
    }

}
