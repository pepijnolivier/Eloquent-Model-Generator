<?php

namespace User11001\EloquentModelGenerator\Console;

use Xethron\MigrationsGenerator\Generators\SchemaGenerator as XethronSchemaGenerator;

class SchemaGenerator extends XethronSchemaGenerator {
    
    /**
     * Returns array of fields matched as primary keys in table
     **/
    function getPrimaryKeys($tableName) {
        $primary_key_index = $this->schema->listTableDetails($tableName)->getPrimaryKey();
        return $primary_key_index ? $primary_key_index->getColumns() : [];
    }
    
    function getSchema() {
        return $this->schema;
    }
    
}
