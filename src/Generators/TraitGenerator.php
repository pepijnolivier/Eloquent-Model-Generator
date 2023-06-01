<?php

namespace Pepijnolivier\EloquentModelGenerator\Generators;

use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Nette\PhpGenerator\PhpFile;
use Pepijnolivier\EloquentModelGenerator\Relations\TableRelations;

class TraitGenerator
{
    public function __construct(
        protected string $traitName,
        protected string $modelNamespaceString,
        protected string $traitNamespaceString,
        protected TableRelations $relations,
    ) {

    }

    public function handle() {
        $traitFile = new PhpFile();
        $traitNamespace = $traitFile->addNamespace($this->traitNamespaceString);
        $traitNamespace->addUse(HasRelationships::class);
        $trait = $traitNamespace->addTrait($this->traitName);

        $trait->addComment("Generated");
        $trait->addTrait(HasRelationships::class);

        (new RelationsGenerator(
            $traitNamespace,
            $trait,
            $this->relations,
            $this->modelNamespaceString
        ))->handle();

        return [$traitFile, $trait];
    }
}
