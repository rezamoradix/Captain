<?php

namespace Tests;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Rey\Captain\Commands\Database\CreateMigrations;

final class MigrationTest extends CommandTestCase
{
    public function testPrompt(): void
    {
        $temp = vfsStream::setup('temp');

        // define("ROOTPATH", __DIR__ . '/');
        // define("APPPATH", __DIR__ . '/');

        $mock = $this->getMockBuilder(CreateMigrations::class)
            ->setConstructorArgs([Services::logger(), Services::commands()])
            ->getMock();

        $this->setPrivateProperty($mock, "dbConfigFile", "");
        $this->setPrivateProperty($mock, "migrationsPath", "");
        
        $mock->run([]);

        // $command = new CreateMigrations(Services::logger(), Services::commands());

        // $command->run([
        //     'override' => true
        // ]);


    }
}
