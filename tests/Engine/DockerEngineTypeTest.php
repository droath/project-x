<?php

namespace Droath\ProjectX\Tests\Engine;

use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestBase;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Console\Output\NullOutput;
use org\bovigo\vfs\vfsStream;

/**
 * Define docker engine type test.
 */
class DockerEngineTypeTest extends TestBase
{
    /**
     * Docker engine object.
     *
     * @var \Droath\ProjectX\Engine\DockerEngineType
     */
    protected $dockerEngine;

    public function setUp()
    {
        parent::setUp();

        $container = Robo::createDefaultContainer(null, new NullOutput(), new ProjectX());
        ProjectX::setDefaultServices($container);
        $builder = $container->get('collectionBuilder', [new Tasks()]);

        $this->dockerEngine = (new DockerEngineType())
            ->setBuilder($builder)
            ->setContainer($container)
            ->setProjectXConfigPath($this->getProjectXFilePath());
    }

    public function testSetupDocker()
    {
        $this->dockerEngine->setupDocker();

        $this->assertProjectFileExists('docker');
        $this->assertProjectFileExists('docker-compose.yml');
        $this->assertGreaterThan(1, array_slice(scandir($this->getProjectFileUrl('docker')), 2));
    }

    public function testSetupDockerSync()
    {
        $this->dockerEngine->setupDockerSync();

        $this->assertProjectFileExists('docker-sync.yml');
        $this->assertProjectFileExists('docker-compose-dev.yml');

        $compose_dev_contents = file_get_contents($this->getProjectFileUrl('docker-compose-dev.yml'));
        $client_ip = ProjectX::clientHostIp();
        $this->assertContains("remote_host={$client_ip}", $compose_dev_contents);

        $env_contents = file_get_contents($this->getProjectFileUrl('.env'));

        $this->assertRegExp('/SYNC_NAME=\w+/', $env_contents);
    }

    public function testHasDockerSync()
    {
        $this->assertFalse($this->dockerEngine->hasDockerSync());
        vfsStream::newFile('docker-sync.yml')
            ->at($this->projectDir);
        $this->assertTrue($this->dockerEngine->hasDockerSync());
    }

}
