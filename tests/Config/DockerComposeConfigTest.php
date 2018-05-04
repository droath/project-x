<?php

namespace Droath\ProjectX\Tests\Config;

use Droath\ProjectX\Config\DockerComposeConfig;
use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Tests\TestBase;

class DockerComposeConfigTest extends TestBase
{
    protected $compose;

    public function setUp() {
        parent::setUp();
        $this->compose = new DockerComposeConfig();
    }

    public function testSetVersion()
    {
        $compose = $this->compose
            ->setVersion(3);

        $this->assertEquals(3, $compose->version);
    }

    public function testSetNetworks()
    {
        $compose = $this->compose
            ->setNetworks([
                'internal',
                'project-x-proxy'
            ]);

        $this->assertEquals([
            'networks' => [
                'internal',
                'project-x-proxy'
            ]
        ], $compose->toArray());
    }

    public function testSetServices()
    {
        $service = (new DockerService())
            ->setImage('nginx')
            ->setLinks([
                'php',
                'mysql'
            ])
            ->setPorts(['80:80'])
            ->setVolumes([
                './:/var/www/html',
                './docker/nginx/nginx.conf:/etc/nginx/nginx.conf'
            ]);

        $compose = $this->compose;
        $compose->setService('nginx', $service);

        $this->assertEquals([
           'services' => [
               'nginx' => [
                    'image' => 'nginx:latest',
                    'links' => [
                        'php',
                        'mysql'
                    ],
                   'ports' => [
                       '80:80'
                   ],
                   'volumes' => [
                       './:/var/www/html',
                       './docker/nginx/nginx.conf:/etc/nginx/nginx.conf'
                   ]
               ]
           ]
        ], $compose->toArray());
    }

    public function testSetVolumes()
    {
        $compose = $this->compose;
        $volumes = [
            'mysql-data' => [
                'driver' => 'local'
            ]
        ];
        $compose->setVolumes($volumes);
        $this->assertEquals($volumes, $compose->volumes);
    }
}
