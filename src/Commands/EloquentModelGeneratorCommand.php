<?php

namespace Pepijnolivier\EloquentModelGenerator\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use KitLoong\MigrationsGenerator\Enum\Driver;
use KitLoong\MigrationsGenerator\Schema\MySQLSchema;
use KitLoong\MigrationsGenerator\Schema\PgSQLSchema;
use KitLoong\MigrationsGenerator\Schema\Schema;
use KitLoong\MigrationsGenerator\Schema\SQLiteSchema;
use KitLoong\MigrationsGenerator\Schema\SQLSrvSchema;
use Pepijnolivier\EloquentModelGenerator\Generators\Generator;
use Pepijnolivier\EloquentModelGenerator\Parser\RelationsParser;

class EloquentModelGeneratorCommand extends Command
{
    public $signature = 'generate:models';
    public $description = 'Generate models from the database with relations.';

    private Schema $schema;
    private RelationsParser $parser;

    public function handle(): int
    {
        $this->schema = $this->getSchema();
        $this->parser = new RelationsParser($this->schema);
        $this->generator = new Generator($this->schema, $this->parser);

        $this->generate();

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
                $this->error("\nFailed to generate model for table $table");
                return;
            }
        }
    }

    /**
     * Get DB schema by the database connection name.
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
            case Driver::MYSQL():
                return $this->schema = app(MySQLSchema::class);

            case Driver::PGSQL():
                return $this->schema = app(PgSQLSchema::class);

            case Driver::SQLITE():
                return $this->schema = app(SQLiteSchema::class);

            case Driver::SQLSRV():
                return $this->schema = app(SQLSrvSchema::class);

            default:
                throw new Exception('The database driver in use is not supported.');
        }
    }
}
