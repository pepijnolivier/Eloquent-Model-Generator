<?php

namespace Pepijnolivier\EloquentModelGenerator\Generators;

use Illuminate\Database\Eloquent\Model;
use Nette\PhpGenerator\PhpFile;

class ModelGenerator
{
    public function __construct(
        protected string $connection,
        protected string $modelName,
        protected string $modelNamespaceString,
    ) { }

    public function handle() {

        $modelFile = new PhpFile();
        $modelNamespace = $modelFile->addNamespace($this->modelNamespaceString);
        $modelNamespace->addUse(Model::class);
        $modelClass = $modelNamespace->addClass($this->modelName);


        // Set the model to use the specified connection
        $connectionProperty = $modelClass->addProperty('connection', $this->connection)
            ->setProtected()
            ->setType('string')
            ->setValue($this->connection);

        // add the "guarded" property as an empty array
        $modelClass->addProperty('guarded', [])
            ->setProtected()
            ->setType('array')
            ->setValue([]);

        $modelClass
            ->setExtends(Model::class)
            ->addComment("Generated")
        ;

        return [$modelFile, $modelClass];
    }
}
