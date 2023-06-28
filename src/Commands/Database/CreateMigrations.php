<?php

/**
 * Migration generator for CodeIgniter
 * requires EasyMigraion trait
 */

namespace Rey\Captain\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateMigrations extends BaseCommand
{
    const INT = "int";
    const BOOLEAN = "boolean";
    const STRING = "string";
    const TEXT = "text";
    const FKEY = "intWithFK";

    private $dataTypes = [
        self::BOOLEAN,
        self::FKEY,
        self::STRING,
        self::INT,
        self::TEXT
    ];

    private $dbConfigFile =  ROOTPATH . "database.conf";
    private $migrationsPath =  APPPATH . "Database/Migrations/";
    private $predefinedFields = [
        'id' => 'id',
        'nanoid' => 'nanoid',
        'dates' => 'timestamps',
        'timestamps' => 'timestamps',
        'createdBy' => 'createdBy',
        'creator' => 'createdBy',
        'maker' => 'createdBy',
    ];

    private $tableNames = [];

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
    protected $usage = 'db:migrations <path> [options]';

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
        if (!is_file($this->dbConfigFile)) {
            CLI::error("Database config file is missing.");
            die;
        }

        $conf = file_get_contents($this->dbConfigFile);

        $this->generateMigrations($conf, (isset($params['override']) && $params['override']));
    }

    public function generateMigrations(string $config, bool $override = false)
    {
        $tables = explode("\n", $config);
        $this->tableNames = array_map(fn ($i) => trim(explode("=", $i)[0]), $tables);

        $exMigs = $this->getExistingMigrations();

        foreach ($tables as $table) {
            $exp = explode("=", $table);
            $tableName = trim($exp[0]);
            $className = pascalize($tableName);
            $fields = explode(" ", trim($exp[1]));

            $generatedMigration = $this->generate($tableName, $className, $fields);

            if (!in_array($className, $exMigs) || $override)
                file_put_contents($this->migrationsPath . $this->basename($className . '.php'), $generatedMigration);
        }
    }

    private function generate(string $tableName, string $className, array $fields)
    {
        helper('inflector');
        $template = file_get_contents(__DIR__ . "/Templates/migration.tpl.php");

        $generatedFields = [];

        foreach ($fields as $key => $field) {
            $data = $this->getFieldData($field);
            $nullable = $data['nullable'] ? "true" : "false";

            if ($this->isPredefined($data['name'])) {
                $generatedFields[] = '$this->' . $this->predefinedFields[$data['name']] . '();';
            } else {
                $type = $data['type'];

                if ($type === self::FKEY)
                    $generatedFields[] = "\t\t" . '$this->' . $type . '(' . $nullable . ', "id", ' . $data['relation'] . ');';
                else
                    $generatedFields[] = "\t\t" . '$this->' . $type . '(' . $nullable . ');';
            }
        }

        $generatedFields[] = '$this->createTable();';
        $fieldsAsText = implode("\n", $generatedFields);

        $generatedMigration = str_replace(["@php", "@class", "@table", '@fields'], ["<?php", $className, $tableName, $fieldsAsText], $template);

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


        // KEY
        if (str_ends_with($fieldName, "_id") && in_array(plural(substr($fieldName, 0, -3)), $this->tableNames))
            return self::FKEY;

        return self::STRING;
    }

    private function getFieldData($field)
    {
        $exp = explode(":", $field);
        $data = [
            'name' => $exp[0]
        ];

        $data['nullable'] = !empty(array_intersect(['null', 'nullable'], $exp));

        $typeIntersect = array_intersect($exp, $this->dataTypes);
        $data['type'] = !empty($typeIntersect) ? $typeIntersect[0] : $this->getFieldType($data);

        if ($data['type'] === self::FKEY) {
            $relationIntersect = array_intersect($exp, $this->tableNames);

            $data['relation'] = !empty($typeIntersect) ? $relationIntersect[0] :
                plural(substr($data['name'], 0, -3));
        }

        return $data;
    }

    private function isPredefined($field)
    {
        return in_array($field, array_keys($this->predefinedFields));
    }

    private function getExistingMigrations()
    {
        helper('filesystem');

        $files = get_filenames($this->migrationsPath);
        $names = array_map(fn ($x) => substr(end(explode("_", $x)), 0, -4), $files);

        return $names;
    }

    /**
     * Change file basename before saving.
     */
    protected function basename(string $filename): string
    {
        return gmdate(config('Migrations')->timestampFormat) . basename($filename);
    }
}
