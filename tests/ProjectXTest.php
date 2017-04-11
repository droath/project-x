<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\ProjectX;
use Robo\Robo;
use org\bovigo\vfs\vfsStream;

/**
 * Define Project-X tests.
 */
class ProjectXTest extends TestBase
{
    public function testGetContainer()
    {
        $this->assertInstanceOf('\League\Container\ContainerInterface', ProjectX::getContainer());
    }

    public function testTaskLocations()
    {
        $locations = ProjectX::taskLocations();

        $this->assertEquals($this->projectRoot, $locations[0]);
        $this->assertEquals('./src/Project/Task/Drupal', $locations[1]);
        $this->assertEquals('./src/Task', $locations[2]);
    }

    public function testProjectRoot()
    {
        $this->assertEquals($this->projectRoot, ProjectX::projectRoot());
    }

    public function testHasProjecConfig()
    {
        $this->assertTrue(ProjectX::hasProjectConfig());
        ProjectX::setProjectPath(null);
        $this->assertFalse(ProjectX::hasProjectConfig());
    }

    public function testGetProjectType()
    {
        $this->assertInstanceOf('\Droath\ProjectX\Project\DrupalProjectType', ProjectX::getProjectType());
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
        $this->assertEquals('project-x-test', $this->projectX->getProjectMachineName());
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
        $this->addProjecXLocalConfigToRoot();

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
