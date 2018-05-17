<?php

namespace Droath\ProjectX\Tests\Engine;

use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Engine\ServiceInterface;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestTaskBase;
use org\bovigo\vfs\vfsStream;

/**
 * Define docker engine type test.
 */
class DockerEngineTypeTest extends TestTaskBase
{
    /**
     * Docker engine object.
     *
     * @var \Droath\ProjectX\Engine\DockerEngineType
     */
    protected $dockerEngine;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->dockerEngine = (new DockerEngineType())
            ->setBuilder($this->builder)
            ->setContainer($this->container);
    }

    public function testSetupDocker()
    {
        $this->dockerEngine->setupDocker();

        $this->assertProjectFileExists('docker');
        $this->assertProjectFileExists('docker-compose.yml');
        $this->assertGreaterThan(1, array_slice(scandir($this->getProjectFileUrl('docker')), 2));
    }

    public function testRebuildDocker()
    {
        // Setup existing docker setup defined in default project-x configuration.
        $this->dockerEngine->setupDocker();

        $config = ProjectX::getProjectConfig();
        $services = $config->getOptions()['docker']['services'];
        $this->assertEquals('apache', $services['web']['type']);
        $this->assertProjectFileExists('docker/services/apache');

        // Update web instance type to be an nginx instead of apache.
        $services['web']['type'] = 'nginx';
        ProjectX::getProjectConfig()->setOptions([
            'docker' => [
                'services' => $services
            ]
        ])->save("{$this->projectRoot}/$this->projectFileName");

        // Invoke docker to be rebuilt.
        $this->dockerEngine->rebuildDocker();

        $this->assertProjectFileExists('docker');
        $this->assertProjectFileExists('docker-compose.yml');
        $this->assertProjectFileExists('docker/services/nginx');

        // Check that nginx is using a Drupal specific service template for default.conf.
        $this->assertEquals('drupal', $config->getType());
        $nginx_file = file_get_contents("{$this->projectRoot}/docker/services/nginx/default.conf");
        $this->assertContains('Drupal', $nginx_file);
    }

    public function testBuildDockerCompose()
    {
        $this->dockerEngine->buildDockerCompose();
        $this->assertProjectFileExists('docker/services/php');
        $this->assertProjectFileExists('docker/services/mysql');
        $this->assertProjectFileExists('docker/services/apache');
        $this->assertProjectFileExists('docker-compose.yml');
    }

    public function testBuildDockerComposeDev()
    {
        $this->dockerEngine->buildDockerComposeDev();
        $this->assertProjectFileExists('docker-compose-dev.yml');
    }

    public function testSetupDockerSync()
    {
        $this->dockerEngine->setupDockerSync();

        $this->assertProjectFileExists('docker-sync.yml');
        $this->assertProjectFileExists('docker-compose-dev.yml');

        $env_contents = file_get_contents($this->getProjectFileUrl('.env'));

        $this->assertRegExp('/SYNC_NAME=\w+/', $env_contents);
        $this->assertRegExp('/HOST_IP_ADDRESS=.*/', $env_contents);

        $client_ip = ProjectX::clientHostIp();
        $this->assertRegExp("/$client_ip/", $env_contents);
    }

    public function testHasDockerSync()
    {
        $this->assertFalse($this->dockerEngine->hasDockerSync());
        vfsStream::newFile('docker-sync.yml')
            ->at($this->projectDir);
        $this->assertTrue($this->dockerEngine->hasDockerSync());
    }

    public function testTemplateDirectories()
    {
        $this->assertEquals([
            './templates/docker',
            './templates/docker/services'
        ], $this->dockerEngine->templateDirectories());
    }

    public function testRequiredPorts()
    {
        $this->assertEquals(['80', '3307', '3306'], $this->dockerEngine->requiredPorts());
    }

    /**
     * @dataProvider dockerServices
     *
     * @param $name
     *   The name of the docker service.
     */
    public function testLoadService($name)
    {
        $service = DockerEngineType::loadService($this->dockerEngine, $name);
        $this->assertInstanceOf(ServiceInterface::class, $service);
    }

    /**
     * Get docker services.
     *
     * @return array
     *   An array of docker services.
     */
    public function dockerServices()
    {
        return [
            ['php'],
            ['redis'],
            ['mysql'],
            ['nginx'],
            ['apache'],
            ['mariadb']
        ];
    }
}
