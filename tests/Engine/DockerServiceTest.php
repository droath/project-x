<?php

namespace Droath\ProjectX\Tests\Engine;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Tests\TestBase;

class DockerServiceTest extends TestBase {

    protected $service;

    public function setUp() {
        parent::setUp();
        $this->service = new DockerService();
    }

    public function testSetImage()
    {
        $service = $this->service;

        $service->setImage('apache', 2.4);
        $this->assertEquals('apache:2.4', $service->image);
    }

    public function testSetBuild()
    {
        $service = $this->service;

        $service->setBuild('./path/to/build');
        $this->assertEquals('./path/to/build', $service->build);
    }

    public function testSetExpose()
    {
        $service = $this->service;

        $service->setExpose(['9000']);
        $this->assertEquals(['9000'], $service->expose);
    }

    public function testSetLinks() {
        $service = $this->service;

        $service->setLinks([
            'php',
            'apache',
        ]);

        $this->assertEquals(['php', 'apache'], $service->links);
    }

    public function testSetDependsOn() {
        $service = $this->service;

        $service->setDependsOn([
            'php',
            'apache',
        ]);

        $this->assertEquals(['php', 'apache'], $service->depends_on);
    }

    public function testSetVolumes()
    {
        $service = $this->service;
        $volumes = [
            './:/var/www/html',
            './docker/nginx/nginx.conf:/etc/nginx/nginx.conf'
        ];
        $service->setVolumes($volumes);
        $this->assertEquals($volumes, $service->volumes);
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->service->isEmpty());
        $this->service->setImage('solr');
        $this->assertFalse($this->service->isEmpty());
    }
}
