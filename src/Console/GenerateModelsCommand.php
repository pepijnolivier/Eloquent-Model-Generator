<?php namespace User11001\EloquentModelGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Way\Generators\Commands\GeneratorCommand;
use \Way\Generators\Generator;
use \Way\Generators\Filesystem\Filesystem;
use \Way\Generators\Compilers\TemplateCompiler;
use \Illuminate\Config\Repository as Config;
use Xethron\MigrationsGenerator\Generators\SchemaGenerator;

class GenerateModelsCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'models:generate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
    protected $description = 'Generate Eloquent models from an existing table structure.';

    /**
     * @param \Way\Generators\Generator  $generator
     * @param \Way\Generators\Filesystem\Filesystem  $file
     * @param \Way\Generators\Compilers\TemplateCompiler  $compiler
     * @param \Illuminate\Config\Repository  $config
     */
    public function __construct(
        Generator $generator,
        Filesystem $file,
        TemplateCompiler $compiler,
        Config $config
    )
    {
        $this->file = $file;
        $this->compiler = $compiler;
        $this->config = $config;

        parent::__construct( $generator );
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
            ['connection', 'c', InputOption::VALUE_OPTIONAL, 'The database connection to use.', $this->config->get( 'database.default' )],
            ['tables', 't', InputOption::VALUE_OPTIONAL, 'A list of Tables you wish to Generate Migrations for separated by a comma: users,posts,comments'],
            ['ignore', 'i', InputOption::VALUE_OPTIONAL, 'A list of Tables you wish to ignore, separated by a comma: users,posts,comments' ],
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


    function generateEloquentModels($eloquentRules) {
        foreach($eloquentRules as $table => $rules) {
            //we will create a new model here
            $hasMany = $rules['hasMany'];
            $hasOne = $rules['hasOne'];
            $belongsTo = $rules['belongsTo'];
            $belongsToMany = $rules['belongsToMany'];

            $namespace = 'App';
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
            $filePathToGenerate .= '/' . $modelName . '.php';

            $templateData = array(
                'NAMESPACE' => $namespace,
                'NAME'=> $modelName,
                'TABLENAME' => $table,
                'FILLABLE'=> $fillable,
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

    function generateFunctions($functionsContainer) {
        $f = '';
        foreach($functionsContainer as $functions) {
            $f .= $functions;
        }
        return $f;
    }

    function generateHasManyFunctions($rulesContainer) {
        $functions = '';
        foreach($rulesContainer as $rules) {
            $hasManyModel = $this->generateModelNameFromTableName($rules[0]);
            $key1 = $rules[1];
            $key2 = $rules[2];

            $hasManyFunctionName = $this->getPluralFunctionName($hasManyModel);

            $function = "
    public function $hasManyFunctionName() {" . '
        return $this->hasMany' . "('App\\$hasManyModel', '$key1', '$key2');
    }
";
            $functions .= $function;
        }
        return $functions;

    }

    function generateHasOneFunctions($rulesContainer) {
        $functions = '';
        foreach($rulesContainer as $rules) {
            $hasOneModel = $this->generateModelNameFromTableName($rules[0]);
            $key1 = $rules[1];
            $key2 = $rules[2];


            $hasOneFunctionName = $this->getSingularFunctionName($hasOneModel);

            $function = "
    public function $hasOneFunctionName() {" . '
        return $this->hasOne' . "('App\\$hasOneModel', '$key1', '$key2');
    }
";
            $functions .= $function;
        }
        return $functions;
    }

    function generateBelongsToFunctions($rulesContainer) {

        $functions = '';
        foreach($rulesContainer as $rules) {
            $belongsToModel = $this->generateModelNameFromTableName($rules[0]);
            $key1 = $rules[1];
            $key2 = $rules[2];


            $belongsToFunctionName = $this->getSingularFunctionName($belongsToModel);

            $function = "
    public function $belongsToFunctionName() {" . '
        return $this->belongsTo' . "('App\\$belongsToModel', '$key1', '$key2');
    }
";
            $functions .= $function;
        }
        return $functions;
    }



    function generateBelongsToManyFunctions($rulesContainer) {
        $functions = '';
        foreach($rulesContainer as $rules) {
            $belongsToManyModel = $this->generateModelNameFromTableName($rules[0]);
            $through = $rules[1];
            $key1 = $rules[2];
            $key2 = $rules[3];

            $belongsToManyFunctionName = $this->getPluralFunctionName($belongsToManyModel);

            $function = "
    public function $belongsToManyFunctionName() {" . '
        return $this->belongsToMany' . "('App\\$belongsToManyModel', '$through', '$key1', '$key2');
    }
";
            $functions .= $function;
        }
        return $functions;
    }

    function getPluralFunctionName($modelName) {
        $pluralFunctionName = strtolower($modelName);
        $pluralFunctionName = rtrim($pluralFunctionName, 's') . 's';
        return $pluralFunctionName;
    }

    function getSingularFunctionName($modelName) {
        $singularFunctionName = strtolower($modelName);
        $singularFunctionName = rtrim($singularFunctionName, 's');
        return $singularFunctionName;
    }

    function generateModelNameFromTableName($table) {
        $modelName = strtolower($table);
        //$modelName = ucfirst($modelName);

        $modelName = $this->snakeToCamel($modelName);

        $modelName = rtrim($modelName, 's');
        return $modelName;
    }

    function snakeToCamel($val) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));
    }


    function getColumnsPrimaryAndForeignKeysPerTable($tables) {
        $prep = [];
        foreach($tables as $table) {
            //get foreign keys
            $foreignKeys = $this->schemaGenerator->getForeignKeyConstraints($table);

            //get primary keys
            $primaryKeys = $this->getPrimaryKeysFromTable($table);

            $columns = $this->getColumnsForTable($table);

            $prep[$table] = [
                'foreign' => $foreignKeys,
                'primary' => $primaryKeys,
                'columns' => $columns
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

    function getPrimaryKeysFromTable($table) {
        $sql = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";
        $primaryKeys = DB::select(DB::raw($sql));

        $prep = [];
        foreach($primaryKeys as $index => $key) {
            $prep[$index] = (array) $key;
        }
        return $prep;
    }

    function getEloquentRules($prep) {

        $rules = [];

        //first create empty ruleset for each table
        foreach($prep as $table => $properties) {
            $rules[$table] = [
                'hasMany' => [],
                'hasOne' => [],
                'belongsTo' => [],
                'belongsToMany' => [],
                'fillable' => [],
            ];
        }

        foreach($prep as $table => $properties) {

            $foreign = $properties['foreign'];
            $primary = $properties['primary'];
            $columns = $properties['columns'];

            $this->setFillableProperties($table, $rules, $columns);


            $isManyToMany = $this->detectManyToMany($prep, $table);

            if($isManyToMany === true) {
                $this->addManyToManyRules($table, $prep, $rules);
            }

            //the below used to be in an ELSE clause but we should be as verbose as possible
            //when we detect a many-to-many table, we still want to set relations on it
            //else
            {
                foreach($foreign as $fk) {
                    $isOneToOne = $this->detectOneToOne($fk, $primary);

                    if($isOneToOne) {
                        $this->addOneToOneRules($table, $prep, $rules, $fk);
                    } else {
                        $this->addOneToManyRules($table, $prep, $rules, $fk);
                    }
                }
            }
        }

        return $rules;
    }

    function setFillableProperties($table, &$rules, $columns) {
        $fillable = [];
        foreach($columns as $item) {
            $col = $item->COLUMN_NAME;

            if(!ends_with($col, '_id') && ($col !== 'id')) {
                if(($col !== 'created_on') && $col !== 'updated_on') {
                    $fillable[] = "'$col'";
                }

            }
        }
        $rules[$table]['fillable'] = $fillable;
    }

    function addOneToManyRules($table, $prep, &$rules, $fk) {
        //$table belongs to $FK
        //FK hasMany $table

        $fkTable = $fk['on'];
        $field = $fk['field'];
        $references = $fk['references'];
        $rules[$fkTable]['hasMany'][] = [$table, $field, $references];
        $rules[$table]['belongsTo'][] = [$fkTable, $field, $references];
    }

    function addOneToOneRules($table, $prep, &$rules, $fk) {
        //$table belongsTo $FK
        //$FK hasOne $table

        $fkTable = $fk['on'];
        $field = $fk['field'];
        $references = $fk['references'];
        $rules[$fkTable]['hasOne'][] = [$table, $field, $references];
        $rules[$table]['belongsTo'][] = [$fkTable, $field, $references];
    }

    function addManyToManyRules($table, $prep, &$rules) {

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


    //if FK is also a primary key, we know this will be a one to one relationship
    function detectOneToOne($fk, $primary) {
        foreach($primary as $prim) {
            if($prim['Column_name'] === $fk['field']) {
                return true;
            }
        }
        return false;
    }

    //does this table have exactly two foreign keys that are also NOT primary,
    //and no tables in the database refer to this table?
    function detectManyToMany($prep, $table) {

        $properties = $prep[$table];
        $foreignKeys = $properties['foreign'];
        $primaryKeys = $properties['primary'];

        //ensure we only have two foreign keys
        if(count($foreignKeys) === 2) {

            //ensure our foreign keys are not also defined as primary keys
            foreach($foreignKeys as $foreign) {
                foreach($primaryKeys as $primary) {
                    if($primary['Column_name'] == $foreign['name']) {
                        return false;
                    }
                }
            }

            //ensure no other tables refer to this one
            foreach($prep as $compareTable => $properties) {
                if($table !== $compareTable) {
                    foreach($properties['foreign'] as $prop) {
                        if($prop['on'] === $table) {
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

    private function initializeSchemaGenerator() {
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
            'NAMESPACE' => 'App'
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

        //$path = $path. '/' . ucwords($this->argument('modelName')) . '.php';
        return $path;
    }

    /**
     * Get the path to the generator template.
     *
     * @return mixed
     */
    protected function getTemplatePath()
    {
        $tp = __DIR__ . '/templates/model.txt';
        return $tp;
    }

}
