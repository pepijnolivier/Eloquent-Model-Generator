<?php

namespace Pepijnolivier\EloquentModelGenerator\Generators;

use Illuminate\Database\Eloquent\Model;
use Nette\PhpGenerator\PhpFile;

class ModelGenerator
{
    public function __construct(
        protected string $modelName,
        protected string $modelNamespaceString,
    ) { }

    public function handle() {

        $modelFile = new PhpFile();
        $modelNamespace = $modelFile->addNamespace($this->modelNamespaceString);
        $modelNamespace->addUse(Model::class);
        $modelClass = $modelNamespace->addClass($this->modelName);

        $modelClass
            ->setExtends(Model::class)
            ->addComment("Generated")
        ;

        return [$modelFile, $modelClass];
    }
}
