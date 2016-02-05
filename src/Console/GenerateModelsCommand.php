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

    /**
     * Fields not included in the generator by default
     *
     * @var array
     */
    protected $excluded_fields = [
      'created_at',
      'updated_at',
    ];

    /**
     * Tables that do not require a generated model
     *
     * @var array
     */
    protected $excluded_tables = [
      'migrations', // laravel's default migration table
    ];

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
            ['tables', InputArgument::OPTIONAL, 'A list of Tables you wish to Generate models for separated by a comma: users,posts,comments'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['connection', 'c', InputOption::VALUE_OPTIONAL, 'The database connection to use.', $this->config->get('database.default')],
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'Where should the file be created?'],
            ['namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Explicitly set the namespace'],
            ['overwrite', 'o', InputOption::VALUE_NONE, 'Overwrite existing models ?'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        //0. determine destination folder
        $destinationFolder = $this->getFileGenerationPath();

        //1. fetch all tables
        $this->info("\nFetching tables...");
        $this->initializeSchemaGenerator();
        $tables = $this->getTables();

        //2. for each table, fetch primary and foreign keys
        $this->info('Fetching table columns, primary keys, foreign keys');
        $prep = $this->getColumnsPrimaryAndForeignKeysPerTable($tables);

        //3. create an array of rules, holding the info for our Eloquent models to be
        $this->info('Generating Eloquent rules');
        $eloquentRules = $this->getEloquentRules($tables, $prep);

        //4. Generate our Eloquent Models
        $this->info("Generating Eloquent models\n");
        $this->generateEloquentModels($destinationFolder, $eloquentRules);

        $this->info("\nAll done!");
    }

    public function getTables() {
        $schemaTables = $this->schemaGenerator->getTables();

        $specifiedTables = $this->argument('tables');

        //when no tables specified, generate all tables
        if(empty($specifiedTables)) {
            return $schemaTables;
        }

        $specifiedTables = explode(',', $specifiedTables);


        $tablesToGenerate = [];
        foreach($specifiedTables as $specifiedTable) {
            if(!in_array($specifiedTable, $schemaTables)) {
                $this->error("specified table not found: $specifiedTable");
            } else {
                $tablesToGenerate[$specifiedTable] = $specifiedTable;
            }
        }

        if(empty($tablesToGenerate)) {
            $this->error('No tables to generate');
            die;
        }

        return array_values($tablesToGenerate);
    }

    private function generateEloquentModels($destinationFolder, $eloquentRules)
    {
        //0. set namespace
        self::$namespace = $this->getNamespace();

        foreach ($eloquentRules as $table => $rules) {
            if(!in_array($table, $this->excluded_tables)) {
                try {
                    $this->generateEloquentModel($destinationFolder, $table,
                      $rules);
                } catch (Exception $e) {
                    $this->error("\nFailed to generate model for table $table");
                    return;
                }
            }
        }
    }

    private function generateEloquentModel($destinationFolder, $table, $rules) {

        //1. Determine path where the file should be generated
        $modelName = $this->generateModelNameFromTableName($table);
        $filePathToGenerate = $destinationFolder . '/'.$modelName.'.php';

        $canContinue = $this->canGenerateEloquentModel($filePathToGenerate, $table);
        if(!$canContinue) {
            return;
        }

        //2.  generate relationship functions and fillable array
        $hasMany = $rules['hasMany'];
        $hasOne = $rules['hasOne'];
        $belongsTo = $rules['belongsTo'];
        $belongsToMany = $rules['belongsToMany'];


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

        //3. prepare template data
        $templateData = array(
            'NAMESPACE' => self::$namespace,
            'NAME' => $modelName,
            'TABLENAME' => $table,
            'FILLABLE' => $fillable,
            'FUNCTIONS' => $functions
        );

        $templatePath = $this->getTemplatePath();

        //run Jeffrey's generator
        $this->generator->make(
            $templatePath,
            $templateData,
            $filePathToGenerate
        );
        $this->info("Generated model for table $table");
    }

    private function canGenerateEloquentModel($filePathToGenerate, $table) {
        $canOverWrite = $this->option('overwrite');
        if(file_exists($filePathToGenerate)) {
            if($canOverWrite) {
                $deleted = unlink($filePathToGenerate);
                if(!$deleted) {
                    $this->warn("Failed to delete existing model $filePathToGenerate");
                    return false;
                }
            } else {
                $this->warn("Skipped model generation, file already exists. (force using --overwrite) $table -> $filePathToGenerate");
                return false;
            }
        }

        return true;
    }

    private function getNamespace() {
        $ns = $this->option('namespace');
        if(empty($ns)) {
            $ns = env('APP_NAME','App\Models');
        }

        //convert forward slashes in the namespace to backslashes
        $ns = str_replace('/', '\\', $ns);
        return $ns;

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
        return $this->hasMany'."(\\".self::$namespace."\\$hasManyModel::class, '$key1', '$key2');
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
        return $this->hasOne'."(\\".self::$namespace."\\$hasOneModel::class, '$key1', '$key2');
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
        return $this->belongsTo'."(\\".self::$namespace."\\$belongsToModel::class, '$key1', '$key2');
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
        return $this->belongsToMany'."(\\".self::$namespace."\\$belongsToManyModel::class, '$through', '$key1', '$key2');
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
            $primaryKeys = $this->schemaGenerator->getPrimaryKeys($table);

            // get columns lists
            $__columns = $this->schemaGenerator->getSchema()->listTableColumns($table);
            $columns = [];
            foreach($__columns as $col) {
                $columns[] = $col->toArray()['name'];
            }

            $prep[$table] = [
                'foreign' => $foreignKeys,
                'primary' => $primaryKeys,
                'columns' => $columns,
            ];
        }

        return $prep;
    }

    private function getEloquentRules($tables, $prep)
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
                $this->addManyToManyRules($tables, $table, $prep, $rules);
            }

            //the below used to be in an ELSE clause but we should be as verbose as possible
            //when we detect a many-to-many table, we still want to set relations on it
            //else
            {
                foreach ($foreign as $fk) {
                    $isOneToOne = $this->detectOneToOne($fk, $primary);

                    if ($isOneToOne) {
                        $this->addOneToOneRules($tables, $table, $rules, $fk);
                    } else {
                        $this->addOneToManyRules($tables, $table, $rules, $fk);
                    }
                }
            }
        }

        return $rules;
    }

    private function setFillableProperties($table, &$rules, $columns, $primary_keys = ['id'])
    {
        $fillable = [];

        $excluded = array_merge($this->excluded_fields, $primary_keys);

        foreach ($columns as $column_name) {
            if (!in_array($column_name, $excluded)) {
                $fillable[] = "'$column_name'";
            }
        }
        $rules[$table]['fillable'] = $fillable;
    }

    private function addOneToManyRules($tables, $table, &$rules, $fk)
    {
        //$table belongs to $FK
        //FK hasMany $table

        $fkTable = $fk['on'];
        $field = $fk['field'];
        $references = $fk['references'];
        if(in_array($fkTable, $tables)) {
            $rules[$fkTable]['hasMany'][] = [$table, $field, $references];
        }
        if(in_array($table, $tables)) {
            $rules[$table]['belongsTo'][] = [$fkTable, $field, $references];
        }
    }

    private function addOneToOneRules($tables, $table, &$rules, $fk)
    {
        //$table belongsTo $FK
        //$FK hasOne $table

        $fkTable = $fk['on'];
        $field = $fk['field'];
        $references = $fk['references'];
        if(in_array($fkTable, $tables)) {
            $rules[$fkTable]['hasOne'][] = [$table, $field, $references];
        }
        if(in_array($table, $tables)) {
            $rules[$table]['belongsTo'][] = [$fkTable, $field, $references];
        }
    }

    private function addManyToManyRules($tables, $table, $prep, &$rules)
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
        if(in_array($fk1Table, $tables)) {
            $rules[$fk1Table]['belongsToMany'][] = [$fk2Table, $table, $fk1Field, $fk2Field];
        }
        if(in_array($fk2Table, $tables)) {
            $rules[$fk2Table]['belongsToMany'][] = [$fk1Table, $table, $fk2Field, $fk1Field];
        }
    }

    //if FK is also a primary key, and there is only one primary key, we know this will be a one to one relationship
    private function detectOneToOne($fk, $primary)
    {
        if (count($primary) === 1) {
            foreach ($primary as $prim) {
                if ($prim === $fk['field']) {
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
                    if ($primary === $foreign['name']) {
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
            null,
            null
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

        if(!is_dir($path)) {
            $this->warn('Path is not a directory, creating ' . $path);
            mkdir($path);
        }

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

        //first try finding the published version
        $publishedTemplatePath = base_path('resources/eloquent-model-generator-templates/model.txt');
        if(is_file($publishedTemplatePath)) {
            return $publishedTemplatePath;
        }

        //just use the default
        $tp = __DIR__.'/templates/model.txt';
        return $tp;

    }
}
