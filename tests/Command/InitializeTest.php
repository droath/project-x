<?php

namespace Droath\ProjectX\Tests\Command;

use Droath\ConsoleForm\FormHelper;
use Droath\ProjectX\Command\Initialize;
use Droath\ProjectX\Form\ProjectXSetup;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestBase;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Tester\CommandTester;
use org\bovigo\vfs\vfsStream;

/**
 * Define the initialize commend test.
 */
class InitializeTest extends TestBase
{
    public function testExecute()
    {
        $app = new ProjectX();
        $app->add(new Initialize());

        $app->getHelperSet()
            ->set($this->questionHelperMock());

        $app->getHelperSet()
            ->set(new FormHelper([
                'project-x.form.setup' => (new ProjectXSetup())->buildForm(),
            ]));

        $root = vfsStream::setup('root');
        $root_url = vfsStream::url('root');

        $filename = 'project-x.yml';

        $command = $app->find('init');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--path' => $root_url,
            ]);
        $output = $commandTester->getDisplay();

        $this->assertTrue($root->hasChild($filename));

        $config = ProjectX::getProjectConfig();

        $this->assertEquals('Project-Test', $config->getName());
        $this->assertEquals('drupal', $config->getType());
        $this->assertEquals('docker', $config->getEngine());
        $this->assertEquals('local.testing.com', $config->getHost()['name']);
        $this->assertEquals('true', $config->getHost()['open_on_startup']);
        $this->assertContains('Success, the project-x configuration have been saved.', $output);
        $this->assertEquals('standard', $config->getOptions()['drupal']['site']['profile']);
        $this->assertEquals('Whatever you Say!!!!', $config->getOptions()['drupal']['site']['name']);
        $this->assertEquals('testing@example.com', $config->getOptions()['drupal']['account']['mail']);
        $this->assertEquals('hacker123', $config->getOptions()['drupal']['account']['name']);
        $this->assertEquals('secret', $config->getOptions()['drupal']['account']['pass']);
        $this->assertContains('Success, the Drupal options have been saved.', $output);
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
                $question = $this->normalizeQuestion($args[2]);

                switch ($question) {
                    case 'project_name':
                        return 'Project-Test';
                    case 'project_type':
                        return 'drupal';
                    case 'select_engine':
                        return 'docker';
                    case 'github_url':
                        return 'https://github.com/droath/project-x';
                    case 'hostname':
                        return 'local.testing.com';
                    case 'drupal_site_name':
                        return 'Whatever you Say!!!!';
                    case 'drupal_site_profile':
                        return 'standard';
                    case 'account_email':
                        return 'testing@example.com';
                    case 'account_username':
                        return 'hacker123';
                    case 'account_password':
                        return 'secret';
                    case 'setup_host':
                    case 'setup_github':
                    case 'save_results':
                    case 'open_browser_on_startup':
                    case 'setup_drupal_site_options':
                    case 'setup_drupal_account_options':
                        return true;
                }
            }));

        return $question_helper;
    }

    protected function normalizeQuestion(Question $question)
    {
        $string = preg_replace('/(\<.+\>.*\<\/\>|\[.+\]|:|\?)/', '', $question->getQuestion());
        $string = trim($string);
        $string = strtr($string, ' ', '_');
        $string = strtolower($string);

        return $string;
    }
}
