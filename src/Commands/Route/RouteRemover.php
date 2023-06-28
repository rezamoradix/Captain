<?php

namespace Rey\Captain\Commands\Route;

use CodeIgniter\CLI\BaseCommand;

class RouteRemover extends BaseCommand
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
    protected $name = 'remove:route';

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
    protected $usage = 'remove:route <path> [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        "method" => "Route method (default: GET)",
        "path" => "The path of route",
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        if (!isset($params[1]) || empty($params[1])) die("path is required");

        $path = $params[1];
        $method = isset($params[0]) && in_array(strtoupper($params[0]), $this->methods) ? strtolower($params[0]) : "GET";

        $routesFile = APPPATH . 'config/Routes.php';

        $routesContent = file_get_contents($routesFile);

        $modified = preg_replace("/(\/\/ ---- Captain::Route $method $path ----).*?(\/\/ ---- Captain::Route $method $path ----)/sui", "", $routesContent);

        // remove empty newlines
        $modified = preg_replace("/\n\n\n+/", "\n", $routesContent);

        file_put_contents($routesFile, $modified);
    }
}
