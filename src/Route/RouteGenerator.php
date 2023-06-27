<?php

namespace Rey\Captain\Route;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;

class RouteGenerator extends BaseCommand
{
    private $methods = ["GET", "POST", "PUT", "PATCH", "DELETE"];

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
    protected $name = 'make:route';

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
    protected $usage = 'make:route <path> [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        "method" => "Route method (default: GET)",
        "path" => "The path of route",
        "controller" => "Route controller"
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = ["filter", "as"];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        if (!isset($params[1]) || empty($params[1])) die("path is required");
        if (!isset($params[2]) || empty($params[2])) die("controller is required");

        $filter = $params['filter'] ?? CLI::getOption('filter');
        $as = $params['as'] ?? CLI::getOption('as');

        $controller = $params[2];
        $path = $params[1];
        $method = isset($params[0]) && in_array(strtoupper($params[0]), $this->methods) ? strtolower($params[0]) : "GET";

        $routesFile = APPPATH . 'config/Routes.php';

        $h = fopen($routesFile, 'a+');
        fwrite($h, $this->generateRoute($method, $path, $controller, $filter, $as));
        fclose($h);
    }

    public function generateRoute($method, $path, $controller, $filter = null, $as = null)
    {
        $opts = [];

        if ($filter) $opts[] = "'filter' => '$filter'";
        if ($as) $opts[] = "'as' => '$as'";

        $optionArg = count($opts) > 0 ? ", [" . implode(', ', $opts) . "]" : null;

        return
            "\n// ---- Captain::Route $method $path ----\n" .
            "\$routes->$method('$path', '$controller'$optionArg);" .
            "\n// ---- Captain::Route $method $path ----";
    }
}
