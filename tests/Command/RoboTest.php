<?php

namespace Droath\ProjectX\Tests\Command;

use Droath\ProjectX\Command\Robo;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestBase;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Tester\CommandTester;
use org\bovigo\vfs\vfsStream;

/**
 * Define the Robo commend test.
 */
class RoboTest extends TestBase
{
    /**
     * Console application.
     *
     * @var \Symfony\Component\Console\Application
     */
    protected $app;

    /**
     * Command object.
     *
     * @var \Symfony\Component\Console\Command\Command
     */
    protected $command;

    public function setUp()
    {
        parent::setUp();

        $this->app = new ProjectX();
        $this->app->add((new Robo())
            ->setProjectXConfigPath($this->getProjectXFilePath()));

        $this->command = $this->app->find('robo');
    }

    public function testExecute()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester
            ->execute([
                'command' => $this->command->getName(),
            ]);

        $this->assertProjectFileExists('RoboFile.php');
    }

    public function testExecuteWithClassNameInputArg()
    {
        $classname = 'TestingRoboFile';

        // Clear the project-x file path, so we can test that
        // passing along the --path option works as expected.
        $this->command->setProjectXConfigPath('');

        $commandTester = new CommandTester($this->command);
        $commandTester
            ->execute([
                'command' => $this->command->getName(),
                '--classname' => $classname,
                '--path' => $this->projectRoot,
            ]);

        $this->assertProjectFileExists("{$classname}.php");
    }

    public function testExecuteWithExistingFile()
    {
        vfsStream::newFile('RoboFile.php')
            ->setContent("<?php print 'RoboFile contents';")
            ->at($this->projectDir);

        // Mock the question helper to respond to the confirm overwrite question.
        $this->app->getHelperSet()
            ->set($this->questionHelperMock());

        $robo_contents = file_get_contents(
            $this->getProjectFileUrl('RoboFile.php')
        );
        // Ensure the file contents exists and has been unchanged.
        $this->assertContains("<?php print 'RoboFile contents';", $robo_contents);

        $commandTester = new CommandTester($this->command);
        $commandTester
            ->execute([
                'command' => $this->command->getName(),
            ]);

        $robo_contents = file_get_contents(
            $this->getProjectFileUrl('RoboFile.php')
        );

        // Check if file contents has been update to match the generated Robo
        // file contents.
        $this->assertRegExp('/class.+Tasks/', $robo_contents);
    }

    protected function questionHelperMock()
    {
        $question_helper = $this
            ->getMockBuilder('\Symfony\Component\Console\Helper\QuestionHelper')
            ->setMethods(['ask'])
            ->getMock();

        $question_helper->expects($this->any())
            ->method('ask')
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $question = $args[2]->getQuestion();

                if (strpos($question, 'Robo file already exists') !== false) {
                    return true;
                }
            }));

        return $question_helper;
    }
}
