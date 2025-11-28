<?php

namespace Pepijnolivier\EloquentModelGenerator\Generators;

use Illuminate\Support\Arr;
use KitLoong\MigrationsGenerator\Schema\Schema;
use Pepijnolivier\EloquentModelGenerator\Parser\RelationsParser;
use Pepijnolivier\EloquentModelGenerator\Relations\TableRelations;
use Pepijnolivier\EloquentModelGenerator\Traits\HelperTrait;

class Generator
{
    use HelperTrait;


    public function __construct(
        protected string $connection,
        protected Schema $schema,
        protected RelationsParser $parser
    ) { }

    public function handle(string $table) {
        $relationsForTable = $this->parser->getRelationsForTable($table);
        $this->generateModelAndTrait($table, $relationsForTable);
    }


    private function generateModelAndTrait(string $tableName, ?TableRelations $relations = null)
    {
        $modelName = $this->generateModelNameFromTableName($tableName);
        $traitName = "Has{$modelName}Relations";

        $modelFolder = $this->getModelFolder();
        $this->createFolderIfNotExists($modelFolder);

        [$modelFile, $modelClass] = (new ModelGenerator(
            $this->connection,
            $tableName,
            $modelName,
            $this->getModelNamespaceString()
        ))->handle();

        $hasRelationships = $relations->hasRelationships() ?? false;
        if($hasRelationships) {
            [$traitFile, $trait] = (new TraitGenerator(
                $traitName,
                $this->getModelNamespaceString(),
                $this->getTraitNamespaceString(),
                $relations
            ))->handle();

            $modelClass->addTrait("{$this->getTraitNamespaceString()}\\$traitName");
            $modelClass->getNamespace()->addUse("{$this->getTraitNamespaceString()}\\$traitName");

            $traitFolder = $this->getTraitFolder();
            $traitPath = "{$traitFolder}/Has{$modelName}Relations.php";
            $this->createFolderIfNotExists($traitFolder);
            file_put_contents($traitPath, (string) $traitFile);
        }

        $modelPath = "{$modelFolder}/{$modelName}.php";
        file_put_contents($modelPath, (string) $modelFile);
    }

    public function getTables(): array
    {
        return $this->schema->getTableNames()->toArray();
    }

    protected function createFolderIfNotExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path);
        }
    }

    private function getModelNamespaceString(): string
    {
        return config(
            'eloquent_model_generator.model_namespace',
            'App\Models\Generated'
        );
    }

    private function getTraitNamespaceString(): string
    {
        return config(
            'eloquent_model_generator.trait_namespace',
            'App\Models\Generated\Relations'
        );
    }

    private function getModelFolder(): string
    {
        return Arr::get(
            $this->getConfig(),
            'model_path',
            'app/Models/Generated'
        );
    }

    private function getTraitFolder(): string
    {
        return Arr::get(
            $this->getConfig(),
            'trait_path',
            'app/Models/Generated/Relations'
        );
    }

    private function getConfig(): array {
        return config('eloquent-model-generator');
    }
}
