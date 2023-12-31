<?php

/**
 * Migration generator for CodeIgniter
 * requires EasyMigraion trait
 */

namespace Rey\Captain\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Rey\Captain\ExistingMigration;

class CreateMigrations extends BaseCommand
{
    const INT = "int";
    const BOOLEAN = "boolean";
    const STRING = "string";
    const DATETIME = "datetime";
    const TEXT = "text";
    const FKEY = "intWithFK";

    const CREATE_TABLE_CODE = '$this->createTable();';
    const DROP_TABLE_CODE = '$this->dropTable();';

    const INDENTS = "\t\t";

    private $dataTypes = [
        self::BOOLEAN,
        self::FKEY,
        self::STRING,
        self::INT,
        self::TEXT,
        self::DATETIME
    ];

    private $dbConfigFile = ROOTPATH . "database.conf";
    private $migrationsPath = APPPATH . "Database/Migrations/";

    private $predefinedFields = [
        'id' => 'id',
        'nanoid' => 'nanoid',
        'dates' => 'timestamps',
        'timestamps' => 'timestamps',
        'createdBy' => 'createdBy',
        'creator' => 'createdBy',
        'maker' => 'createdBy',
    ];

    /**
     * List of table names
     *
     * @var array
     */
    private $tableNames = [];

    /**
     * List of finished (processed) table names
     * This list is being updated everytime a migration is created
     *
     * @var array
     */
    private $finishedTableNames = [];

    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Captain';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'db:migrations';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = '';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'db:migrations [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        'override'
    ];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        helper('inflector');

        if (!is_file($this->dbConfigFile)) {
            CLI::error("Database config file is missing. [value: $this->dbConfigFile]");
            die;
        }

        $override = isset($params['override']) && $params['override'];

        $conf = file_get_contents($this->dbConfigFile);

        $this->generateMigrations($conf, $override);
    }

    public function generateMigrations(string $config, bool $override = false)
    {
        $tables = array_filter(explode("\n", $config), fn ($x) => !empty($x));
        $this->tableNames = array_map(fn ($i) => trim(explode("=", $i)[0]), $tables);

        $existingMigrations = $this->getExistingMigrations();
        $existingMigrationNames = array_map(fn ($x) => $x->name, $existingMigrations);

        foreach ($tables as $table) {
            $exp = explode("=", $table);
            $tableName = trim($exp[0]);
            $className = pascalize($tableName);
            $fields = explode(" ", trim($exp[1]));

            /**
             * Check if tableName is existed, so generate alterTable instead of newTable
             */
            $isAlterTable = in_array($tableName, $this->finishedTableNames);

            $generatedMigration = $isAlterTable ? $this->generateAlterTable($tableName, $className, $fields) : $this->generateNewTable($tableName, $className, $fields);

            if ($isAlterTable) {
                // TODO
            } else {
                if (in_array($className, $existingMigrationNames) && $override)
                    file_put_contents($this->migrationsPath . $existingMigrations[array_search($className, $existingMigrationNames)]->path, $generatedMigration);

                else if (!in_array($className, $existingMigrationNames))
                    file_put_contents($this->migrationsPath . $this->basename($className . '.php'), $generatedMigration);
            }

            $this->finishedTableNames[] = $tableName;
        }
    }

    private function generateNewTable(string $tableName, string $className, array $fields)
    {
        $template = file_get_contents(__DIR__ . "/Templates/migration.tpl");

        $generatedFields = [];

        foreach ($fields as $key => $field) {
            $data = $this->getFieldData($field);
            $nullable = $data['nullable'] ? "true" : "false";

            if ($this->isPredefined($data['name'])) {
                $generatedFields[] = self::INDENTS . '$this->' . $this->predefinedFields[$data['name']] . '();';
            } else {
                $type = $data['type'];

                if ($type === self::FKEY) {
                    $onUpdate = $data['update-cascade'] ? 'CASCADE' : '';
                    $onDelete = $data['delete-cascade'] ? 'CASCADE' : '';

                    $generatedFields[] = self::INDENTS . '$this->' . $type . '("' . $data['name'] . '", ' . $nullable . ', "id", "' . $data['relation'] . '", "' . $onUpdate . '", "' . $onDelete . '");';
                } else
                    $generatedFields[] = self::INDENTS . '$this->' . $type . '("' . $data['name'] . '", ' . $nullable . ');';

                // index, unique
                if ($data['index'])
                    $generatedFields[] = self::INDENTS . '$this->forge->addKey("' . $data['name'] . '", false, false, "' . $data['name'] . '_idx");';

                if ($data['unique'])
                    $generatedFields[] = self::INDENTS . '$this->forge->addUniqueKey("' . $data['name'] . '", "' . $data['name'] . '_uniq");';
            }
        }

        $generatedFields[] = self::INDENTS . self::CREATE_TABLE_CODE;
        $fieldsAsText = implode("\n", $generatedFields);

        $generatedMigration = str_replace(["@php", "@class", "@table", '@fields', '@down'], ["<?php", $className, $tableName, $fieldsAsText, self::DROP_TABLE_CODE], $template);

        return $generatedMigration;
    }

    private function generateAlterTable(string $tableName, string $className, array $fields)
    {
        $template = file_get_contents(__DIR__ . "/Templates/migration.tpl");

        $generatedFields = [];

        foreach ($fields as $key => $field) {
            $data = $this->getFieldData($field);
            $nullable = $data['nullable'] ? "true" : "false";

            if ($this->isPredefined($data['name'])) {
                $generatedFields[] = '$this->' . $this->predefinedFields[$data['name']] . '();';
            } else {
                $type = $data['type'];

                if ($type === self::FKEY)
                    $generatedFields[] = self::INDENTS . '$this->' . $type . '("' . $data['name'] . '", ' . $nullable . ', "id", ' . $data['relation'] . ');';
                else
                    $generatedFields[] = self::INDENTS . '$this->' . $type . '("' . $data['name'] . '", ' . $nullable . ');';
            }
        }

        $generatedFields[] = self::INDENTS . self::CREATE_TABLE_CODE;
        $fieldsAsText = implode("\n", $generatedFields);

        $generatedMigration = str_replace(["@php", "@class", "@table", '@fields', '@down'], ["<?php", $className, $tableName, $fieldsAsText, self::DROP_TABLE_CODE], $template);

        return $generatedMigration;
    }

    private function getFieldType($fieldData)
    {
        $fieldName = $fieldData['name'];

        // BOOL
        if (
            str_starts_with($fieldName, "is_") || str_starts_with($fieldName, "has_") || str_starts_with($fieldName, "have_")
            || str_starts_with($fieldName, "should_") || str_starts_with($fieldName, "can_") || str_starts_with($fieldName, "may_")
        )
            return self::BOOLEAN;

        if (str_ends_with($fieldName, "able"))
            return self::BOOLEAN;


        // INT
        if (str_starts_with($fieldName, "number_") || str_ends_with($fieldName, "_number"))
            return self::INT;

        if (str_ends_with($fieldName, "amount") || str_ends_with($fieldName, "quantity") || str_ends_with($fieldName, "price"))
            return self::INT;

        if (str_ends_with($fieldName, "_days") || str_ends_with($fieldName, "_years"))
            return self::INT;


        // TEXT
        if (str_ends_with($fieldName, "body"))
            return self::TEXT;

        if (str_ends_with($fieldName, "text"))
            return self::TEXT;

        // DATE
        if (str_ends_with($fieldName, "_AT"))
            return self::DATETIME;

        // KEY
        if (str_ends_with($fieldName, "_id") && in_array(plural(substr($fieldName, 0, -3)), $this->tableNames))
            return self::FKEY;

        return self::STRING;
    }

    private function getFieldData($field)
    {
        $exp = explode(":", $field);
        $sliced = count($exp) == 1 ? [] : array_slice($exp, 1);
        $data = [
            'name' => $exp[0]
        ];

        $data['nullable'] = !empty(array_intersect(['null', 'nullable'], $sliced));

        $typeIntersect = array_intersect($sliced, $this->dataTypes);
        $data['type'] = !empty($typeIntersect) ? $typeIntersect[array_key_first($typeIntersect)] : $this->getFieldType($data);

        if ($data['type'] === self::FKEY) {
            $relationIntersect = array_intersect($exp, $this->tableNames);

            $data['relation'] = !empty($typeIntersect) ? $relationIntersect[0] :
                plural(substr($data['name'], 0, -3));
        }

        $data['index'] = in_array('index', $sliced);
        $data['unique'] = in_array('unique', $sliced);

        $cascadeBoth = in_array('cascade', $sliced);

        $data['update-cascade'] = $cascadeBoth || in_array('update-cascade', $sliced);
        $data['delete-cascade'] = $cascadeBoth || in_array('delete-cascade', $sliced);

        return $data;
    }

    private function isPredefined($field)
    {
        return in_array($field, array_keys($this->predefinedFields));
    }

    /**
     * @return ExistingMigration[]
     */
    private function getExistingMigrations()
    {
        helper('filesystem');

        $files = get_filenames($this->migrationsPath);

        $migs = [];

        foreach ($files as $key => $file) {
            $_exp = explode('_', $file);
            $_end = end($_exp);
            $migs[] = new ExistingMigration(substr($_end, 0, -4), $file);
        }

        return $migs;
    }

    /**
     * Change file basename before saving.
     */
    protected function basename(string $filename): string
    {
        return gmdate(config('Migrations')->timestampFormat) . basename($filename);
    }
}
