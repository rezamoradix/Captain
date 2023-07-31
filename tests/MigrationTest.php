<?php

namespace Tests;

require_once '_support/CommandTestCase.php';

use CodeIgniter\Config\Services;
use org\bovigo\vfs\vfsStream;
use Rey\Captain\Commands\Database\CreateMigrations;
use Tests\Support\CommandTestCase;

final class MigrationTest extends CommandTestCase
{
    public function testPrompt(): void
    {
        /**
         * Creating a temp filesystem in order to store  files 
         */
        $temp = vfsStream::setup('temp');

        $mock = new CreateMigrations(Services::logger(), Services::commands());

        $migsDir = $temp->url() . '/migs';

        mkdir($migsDir);

        $this->setPrivateProperty($mock, "dbConfigFile", HOMEPATH . "tests/test_database.conf");
        $this->setPrivateProperty($mock, "migrationsPath", $migsDir);
        
        $mock->run([]);

        var_dump($temp->getChildren());
    }
}
