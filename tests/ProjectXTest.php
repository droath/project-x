<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\ProjectX;
use org\bovigo\vfs\vfsStream;

/**
 * Define Project-X tests.
 */
class ProjectXTest extends TestBase
{
    /**
     * Project-X object.
     *
     * @var \Droath\ProjectX\ProjectX
     */
    protected $projectX;

    public function setUp()
    {
        parent::setUp();

        $path = $this->getProjectFileUrl($this->projectFileName);
        $this->projectX = (new ProjectX())
            ->setProjectXConfigPath($path);
    }

    public function testProjectMachineName()
    {
        $this->assertEquals('project-x_test', $this->projectX->getProjectMachineName());
    }

    public function testLoadRoboProjectClasses()
    {
        $classes = $this->projectX->loadRoboProjectClasses();
        $this->assertEmpty($classes);

        // Create a RoboFileTest.php in project directory.
        $classname = 'RoboFileTest';
        vfsStream::newFile("{$classname}.php")
            ->setContent($this->generateRoboClass($classname))
            ->at($this->projectDir);

        $classes = $this->projectX->loadRoboProjectClasses();

        $this->assertNotEmpty($classes);
        $this->assertEquals($classname, $classes[0]);
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
