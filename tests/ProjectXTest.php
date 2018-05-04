<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\ProjectX;
use org\bovigo\vfs\vfsStream;
use phpDocumentor\Reflection\Project;

/**
 * Define Project-X tests.
 */
class ProjectXTest extends TestBase
{
    public function testDiscoverCommands()
    {
        $this->projectX->discoverCommands();
        $this->assertInstanceOf('\Droath\ProjectX\Command\Robo', $this->projectX->find('robo'));
        $this->assertInstanceOf('\Droath\ProjectX\Command\Initialize', $this->projectX->find('init'));
    }

    public function testGetEnvVariables()
    {
        vfsStream::create([
            'docroot' => [],
            '.env' => "SYNC_NAME=project-x-test\nHOST_IP=127.0.0.1"
        ], $this->projectDir);

        ProjectX::setEnvVariables();

        $this->assertEquals('127.0.0.1', getenv('HOST_IP'));
        $this->assertEquals('project-x-test', getenv('SYNC_NAME'));
        $this->assertEquals(2, count(ProjectX::getEnvVariables()));
    }

    public function testGetContainer()
    {
        $this->assertInstanceOf('\League\Container\ContainerInterface', ProjectX::getContainer());
    }

    public function testTaskLocations()
    {
        $locations = ProjectX::taskLocations();

        $this->assertEquals($this->projectRoot, $locations[0]);
        $this->assertEquals('./src/Project/Task/Drupal', $locations[1]);
        $this->assertEquals('./src/Project/Task/PHP', $locations[2]);
        $this->assertEquals('./src/Task', $locations[3]);
    }

    public function testProjectRoot()
    {
        $this->assertEquals($this->projectRoot, ProjectX::projectRoot());
    }

    public function testHasProjectConfig()
    {
        $this->assertTrue(ProjectX::hasProjectConfig());
        ProjectX::setProjectPath(null);
        $this->assertFalse(ProjectX::hasProjectConfig());
    }

    public function testGetProjectType()
    {
        $this->assertInstanceOf('\Droath\ProjectX\Project\DrupalProjectType', ProjectX::getProjectType());
    }

    public function testGetEngineType()
    {
        $this->assertInstanceOf('\Droath\ProjectX\Engine\DockerEngineType', ProjectX::getEngineType());
    }

    public function testBuildRoot()
    {
        $this->assertEquals('vfs://root/build', ProjectX::buildRoot());
    }

    public function testProjectType()
    {
        $this->assertEquals('drupal', ProjectX::projectType());
    }

    public function testEngineType()
    {
        $this->assertEquals('docker', ProjectX::engineType());

    }

    public function testGetProjectConfig()
    {
        $this->assertInstanceOf(
            '\Droath\ProjectX\Config\ProjectXConfig',
            ProjectX::getProjectConfig()
        );
    }

    public function testProjectMachineName()
    {
        $this->assertEquals('project-x-test', ProjectX::getProjectMachineName());
    }

    public function testGetConfig()
    {
        $config = ProjectX::getProjectConfig();
        $this->assertEquals('drupal', $config->getType());
        $this->assertEquals('Project-X Test', $config->getName());
        $this->assertEquals('true', $config->getHost()['open_on_startup']);
    }

    public function testGetConfigOverrideLocal()
    {
        $this->addProjectXLocalConfigToRoot();

        $config = ProjectX::getProjectConfig();
        $this->assertEquals('drupal', $config->getType());
        $this->assertEquals('Project-X Local', $config->getName());
        $this->assertEquals('false', $config->getHost()['open_on_startup']);
    }

    /**
     * Generate the Robo class.
     *
     * @param string $classname
     *   The classname.
     *
     * @return string
     *   The Robo class contents.
     */
    protected function generateRoboClass($classname)
    {
        return  '<?php'
            . "\n/**"
            . "\n * This is project's console commands configuration for Robo task runner."
            . "\n *"
            . "\n * @see http://robo.li/"
            . "\n */"
            . "\nclass " . $classname . " extends \\Robo\\Tasks\n{\n    // define public methods as commands\n}";
    }
}
