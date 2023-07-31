<?php

namespace Tests\Support;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CommandTestCase extends CIUnitTestCase
{
    public function mockCommand(string $className): MockObject
    {
        $mock = $this->getMockBuilder($className)
            ->setConstructorArgs([Services::logger(), Services::commands()])
            ->getMock();

        return $mock;
    }
}
