<?php

namespace User11001\EloquentModelGenerator\Console;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Way\Generators\Commands\GeneratorCommand;
use Way\Generators\Generator;
use Way\Generators\Filesystem\Filesystem;
use Way\Generators\Compilers\TemplateCompiler;
use Illuminate\Contracts\Config\Repository as Config;
use Xethron\MigrationsGenerator\Generators\SchemaGenerator;

class GenerateModelsCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'models:generate';

    private static $namespace;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Eloquent models from an existing table structure.';

    private $schemaGenerator;
    /**
     * @param Generator  $generator
     * @param Filesystem  $file
     * @param TemplateCompiler  $compiler
     * @param Config  $config
     */
    public function __construct(
        Generator $generator,
        Filesystem $file,
        TemplateCompiler $compiler,
        Config $config
    ) {
        $this->file = $file;
        $this->compiler = $compiler;
        $this->config = $config;

        parent::__construct($generator);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['tables', InputArgument::OPTIONAL, 'A list of Tables you wish to Generate Migrations for separated by a comma: users,posts,comments'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        //shameless copy
        return [
            ['connection', 'c', InputOption::VALUE_OPTIONAL, 'The database connection to use.', $this->config->get('database.default')],
            ['tables', 't', InputOption::VALUE_OPTIONAL, 'A list of Tables you wish to Generate Migrations for separated by a comma: users,posts,comments'],
            ['ignore', 'i', InputOption::VALUE_OPTIONAL, 'A list of Tables you wish to ignore, separated by a comma: users,posts,comments'],
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'Where should the file be created?'],
            ['templatePath', 'tp', InputOption::VALUE_OPTIONAL, 'The location of the template for this generator'],
            ['defaultIndexNames', null, InputOption::VALUE_NONE, 'Don\'t use db index names for migrations'],
            ['defaultFKNames', null, InputOption::VALUE_NONE, 'Don\'t use db foreign key names for migrations'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        //1. fetch all tables
        $this->info("\nFetching tables...");
        $this->initializeSchemaGenerator();
        $tables = $this->schemaGenerator->getTables();

        //2. for each table, fetch primary and foreign keys
        $this->info('Fetching table columns, primary keys, foreign keys');
        $prep = $this->getColumnsPrimaryAndForeignKeysPerTable($tables);

        //3. create an array of rules, holding the info for our Eloquent models to be
        $this->info('Generating Eloquent rules');
        $eloquentRules = $this->getEloquentRules($prep);

        //4. Generate our Eloquent Models
        $this->info('Generating Eloquent models');
        $this->generateEloquentModels($eloquentRules);

        $this->info("\nAll done!");
    }

    private function generateEloquentModels($eloquentRules)
    {
        foreach ($eloquentRules as $table => $rules) {
            //we will create a new model here
            $hasMany = $rules['hasMany'];
            $hasOne = $rules['hasOne'];
            $belongsTo = $rules['belongsTo'];
            $belongsToMany = $rules['belongsToMany'];

            self::$namespace = env('APP_NAME','App\Models');
            $modelName = $this->generateModelNameFromTableName($table);
            $fillable = implode(', ', $rules['fillable']);

            $belongsToFunctions = $this->generateBelongsToFunctions($belongsTo);
            $belongsToManyFunctions = $this->generateBelongsToManyFunctions($belongsToMany);
            $hasManyFunctions = $this->generateHasManyFunctions($hasMany);
            $hasOneFunctions = $this->generateHasOneFunctions($hasOne);

            $functions = $this->generateFunctions([
                $belongsToFunctions,
                $belongsToManyFunctions,
                $hasManyFunctions,
                $hasOneFunctions,
            ]);

            $filePathToGenerate = $this->getFileGenerationPath();
            $filePathToGenerate .= '/'.$modelName.'.php';

            $templateData = array(
                'NAMESPACE' => self::$namespace,
                'NAME' => $modelName,
                'TABLENAME' => $table,
                'FILLABLE' => $fillable,
                'FUNCTIONS' => $functions
            );

            $templatePath = $this->getTemplatePath();

            $this->generator->make(
                $templatePath,
                $templateData,
                $filePathToGenerate
            );
        }
    }

    private function generateFunctions($functionsContainer)
    {
        $f = '';
        foreach ($functionsContainer as $functions) {
            $f .= $functions;
        }

        return $f;
    }

    private function generateHasManyFunctions($rulesContainer)
    {
        $functions = '';
        foreach ($rulesContainer as $rules) {
            $hasManyModel = $this->generateModelNameFromTableName($rules[0]);
            $key1 = $rules[1];
            $key2 = $rules[2];

            $hasManyFunctionName = $this->getPluralFunctionName($hasManyModel);

            $function = "
    public function $hasManyFunctionName() {".'
        return $this->hasMany'."('".self::$namespace."\\$hasManyModel', '$key1', '$key2');
    }
";
            $functions .= $function;
        }

        return $functions;
    }

    private function generateHasOneFunctions($rulesContainer)
    {
        $functions = '';
        foreach ($rulesContainer as $rules) {
            $hasOneModel = $this->generateModelNameFromTableName($rules[0]);
            $key1 = $rules[1];
            $key2 = $rules[2];

            $hasOneFunctionName = $this->getSingularFunctionName($hasOneModel);

            $function = "
    public function $hasOneFunctionName() {".'
        return $this->hasOne'."('".self::$namespace."\\$hasOneModel', '$key1', '$key2');
    }
";
            $functions .= $function;
        }

        return $functions;
    }

    private function generateBelongsToFunctions($rulesContainer)
    {
        $functions = '';
        foreach ($rulesContainer as $rules) {
            $belongsToModel = $this->generateModelNameFromTableName($rules[0]);
            $key1 = $rules[1];
            $key2 = $rules[2];

            $belongsToFunctionName = $this->getSingularFunctionName($belongsToModel);

            $function = "
    public function $belongsToFunctionName() {".'
        return $this->belongsTo'."('".self::$namespace."\\$belongsToModel', '$key1', '$key2');
    }
";
            $functions .= $function;
        }

        return $functions;
    }

    private function generateBelongsToManyFunctions($rulesContainer)
    {
        $functions = '';
        foreach ($rulesContainer as $rules) {
            $belongsToManyModel = $this->generateModelNameFromTableName($rules[0]);
            $through = $rules[1];
            $key1 = $rules[2];
            $key2 = $rules[3];

            $belongsToManyFunctionName = $this->getPluralFunctionName($belongsToManyModel);

            $function = "
    public function $belongsToManyFunctionName() {".'
        return $this->belongsToMany'."('".self::$namespace."\\$belongsToManyModel', '$through', '$key1', '$key2');
    }
";
            $functions .= $function;
        }

        return $functions;
    }

    private function getPluralFunctionName($modelName)
    {
        $modelName = lcfirst($modelName);
        return str_plural($modelName);
    }

    private function getSingularFunctionName($modelName)
    {
        $modelName = lcfirst($modelName);
        return str_singular($modelName);
    }

    private function generateModelNameFromTableName($table)
    {
        return ucfirst(camel_case(str_singular($table)));
    }


    private function getColumnsPrimaryAndForeignKeysPerTable($tables)
    {
        $prep = [];
        foreach ($tables as $table) {
            //get foreign keys
            $foreignKeys = $this->schemaGenerator->getForeignKeyConstraints($table);

            //get primary keys
            $primaryKeys = $this->getPrimaryKeysFromTable($table);

            $columns = $this->getColumnsForTable($table);

            $prep[$table] = [
                'foreign' => $foreignKeys,
                'primary' => $primaryKeys,
                'columns' => $columns,
            ];
        }

        return $prep;
    }

    private function getColumnsForTable($table)
    {
        $db = \Config::get('database')['connections']['mysql']['database'];
        $sql = "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA='$db'
                AND TABLE_NAME='$table'";

        $columns = DB::select(DB::raw($sql));

        return $columns;
    }

    private function getPrimaryKeysFromTable($table)
    {
        $sql = "SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'";
        $primaryKeys = DB::select(DB::raw($sql));

        $prep = [];
        foreach ($primaryKeys as $index => $key) {
            $prep[$index] = (array) $key;
        }

        return $prep;
    }

    private function getEloquentRules($prep)
    {
        $rules = [];

        //first create empty ruleset for each table
        foreach ($prep as $table => $properties) {
            $rules[$table] = [
                'hasMany' => [],
                'hasOne' => [],
                'belongsTo' => [],
                'belongsToMany' => [],
                'fillable' => [],
            ];
        }

        foreach ($prep as $table => $properties) {
            $foreign = $properties['foreign'];
            $primary = $properties['primary'];
            $columns = $properties['columns'];

            $this->setFillableProperties($table, $rules, $columns);

            $isManyToMany = $this->detectManyToMany($prep, $table);

            if ($isManyToMany === true) {
                $this->addManyToManyRules($table, $prep, $rules);
            }

            //the below used to be in an ELSE clause but we should be as verbose as possible
            //when we detect a many-to-many table, we still want to set relations on it
            //else
            {
                foreach ($foreign as $fk) {
                    $isOneToOne = $this->detectOneToOne($fk, $primary);

                    if ($isOneToOne) {
                        $this->addOneToOneRules($table, $rules, $fk);
                    } else {
                        $this->addOneToManyRules($table, $rules, $fk);
                    }
                }
            }
        }

        return $rules;
    }

    private function setFillableProperties($table, &$rules, $columns)
    {
        $fillable = [];
        foreach ($columns as $item) {
            $col = $item->COLUMN_NAME;

            if (!ends_with($col, '_id') && $col !== 'id' && $col !== 'created_on' && $col !== 'updated_on') {
                $fillable[] = "'$col'";
            }
        }
        $rules[$table]['fillable'] = $fillable;
    }

    private function addOneToManyRules($table, &$rules, $fk)
    {
        //$table belongs to $FK
        //FK hasMany $table

        $fkTable = $fk['on'];
        $field = $fk['field'];
        $references = $fk['references'];
        $rules[$fkTable]['hasMany'][] = [$table, $field, $references];
        $rules[$table]['belongsTo'][] = [$fkTable, $field, $references];
    }

    private function addOneToOneRules($table, &$rules, $fk)
    {
        //$table belongsTo $FK
        //$FK hasOne $table

        $fkTable = $fk['on'];
        $field = $fk['field'];
        $references = $fk['references'];
        $rules[$fkTable]['hasOne'][] = [$table, $field, $references];
        $rules[$table]['belongsTo'][] = [$fkTable, $field, $references];
    }

    private function addManyToManyRules($table, $prep, &$rules)
    {

        //$FK1 belongsToMany $FK2
        //$FK2 belongsToMany $FK1

        $foreign = $prep[$table]['foreign'];

        $fk1 = $foreign[0];
        $fk1Table = $fk1['on'];
        $fk1Field = $fk1['field'];
        //$fk1References = $fk1['references'];

        $fk2 = $foreign[1];
        $fk2Table = $fk2['on'];
        $fk2Field = $fk2['field'];
        //$fk2References = $fk2['references'];

        //User belongstomany groups user_group, user_id, group_id
        $rules[$fk1Table]['belongsToMany'][] = [$fk2Table, $table, $fk1Field, $fk2Field];
        $rules[$fk2Table]['belongsToMany'][] = [$fk1Table, $table, $fk2Field, $fk1Field];
    }

    //if FK is also a primary key, and there is only one primary key, we know this will be a one to one relationship
    private function detectOneToOne($fk, $primary)
    {
        if (count($primary) === 1) {
            foreach ($primary as $prim) {
                if ($prim['Column_name'] === $fk['field']) {
                    return true;
                }
            }
        }

        return false;
    }

    //does this table have exactly two foreign keys that are also NOT primary,
    //and no tables in the database refer to this table?
    private function detectManyToMany($prep, $table)
    {
        $properties = $prep[$table];
        $foreignKeys = $properties['foreign'];
        $primaryKeys = $properties['primary'];

        //ensure we only have two foreign keys
        if (count($foreignKeys) === 2) {

            //ensure our foreign keys are not also defined as primary keys
            $primaryKeyCountThatAreAlsoForeignKeys = 0;
            foreach ($foreignKeys as $foreign) {
                foreach ($primaryKeys as $primary) {
                    if ($primary['Column_name'] === $foreign['name']) {
                        ++$primaryKeyCountThatAreAlsoForeignKeys;
                    }
                }
            }

            if ($primaryKeyCountThatAreAlsoForeignKeys === 1) {
                //one of the keys foreign keys was also a primary key
                //this is not a many to many. (many to many is only possible when both or none of the foreign keys are also primary)
                return false;
            }

            //ensure no other tables refer to this one
            foreach ($prep as $compareTable => $properties) {
                if ($table !== $compareTable) {
                    foreach ($properties['foreign'] as $prop) {
                        if ($prop['on'] === $table) {
                            return false;
                        }
                    }
                }
            }
            //this is a many to many table!
            return true;
        }

        return false;
    }

    private function initializeSchemaGenerator()
    {
        $this->schemaGenerator = new SchemaGenerator(
            $this->option('connection'),
            $this->option('defaultIndexNames'),
            $this->option('defaultFKNames')
        );

        return $this->schemaGenerator;
    }

    /**
     * Fetch the template data.
     *
     * @return array
     */
    protected function getTemplateData()
    {
        return [
            'NAME' => ucwords($this->argument('modelName')),
            'NAMESPACE' => env('APP_NAME','App\Models'),
        ];
    }

    /**
     * The path to where the file will be created.
     *
     * @return mixed
     */
    protected function getFileGenerationPath()
    {
        $path = $this->getPathByOptionOrConfig('path', 'model_target_path');

        return $path;
    }

    /**
     * Get the path to the generator template.
     *
     * @return mixed
     */
    protected function getTemplatePath()
    {
        $tp = __DIR__.'/templates/model.txt';

        return $tp;
    }
}
