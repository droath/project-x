<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\Database;

class DatabaseTest extends TestBase
{
    protected $database;

    public function setUp() {
        $this->database = new Database();
    }

    public function testCreateFromArray()
    {
        $data = [
            'user' => 'admin',
            'password' => 'secret',
            'port'  => 7777,
            'database' => 'project-x'
        ];
        $instance = Database::createFromArray($data);
        $this->assertEquals($data, (array) $instance->asArray());
    }
}
