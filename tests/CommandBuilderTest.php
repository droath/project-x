<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\CommandBuilder;
use PHPUnit\Framework\TestCase;

class CommandBuilderTest extends TestCase
{
    protected $commandBuilder;

    public function setUp()
    {
        parent::setUp();
        $this->commandBuilder = new CommandBuilder('gunzip');
    }

    public function testCommand()
    {
        $this->commandBuilder->command('filename.tgz');
        $this->assertEquals('gunzip filename.tgz', $this->commandBuilder->build());
    }

    public function testSetExecutable()
    {
        $this->commandBuilder->setExecutable('test');
        $this->assertEquals('test', $this->commandBuilder->build());
    }

    public function testSetOption()
    {
        $this->commandBuilder->setOption('test', 'value');
        $this->assertEquals('gunzip --test value', $this->commandBuilder->build());
    }

    public function testSetEnvVariable()
    {
        $this->commandBuilder->setEnvVariable('USERNAME', 'sandstorm');
        $this->assertEquals('USERNAME=sandstorm gunzip', $this->commandBuilder->build());
    }
}
