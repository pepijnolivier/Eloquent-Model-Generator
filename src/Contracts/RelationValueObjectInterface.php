<?php

namespace Pepijnolivier\EloquentModelGenerator\Contracts;

interface RelationValueObjectInterface
{
    public function getRelation(): RelationInterface;
}
