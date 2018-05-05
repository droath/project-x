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
        $this->assertEquals(8, $config->getVersion());
        $this->assertEquals('stg', $config->getRemote()['environments'][0]['realm']);
        $this->assertEquals('Development', $config->getRemote()['environments'][0]['name']);
        $this->assertEquals('/var/www/html/docroot', $config->getRemote()['environments'][0]['path']);
        $this->assertEquals('dev.project-x.com', $config->getRemote()['environments'][0]['uri']);
        $this->assertEquals('admin@dev.project-x.com', $config->getRemote()['environments'][0]['ssh_url']);
        $this->assertEquals('docker', $config->getEngine());
        $this->assertEquals('local.testing.com', $config->getHost()['name']);
        $this->assertEquals('true', $config->getHost()['open_on_startup']);
        $this->assertEquals('true', $config->getNetwork()['proxy']);
        $this->assertContains('Success, the project-x configuration have been saved.', $output);
        $this->assertEquals('standard', $config->getOptions()['drupal']['site']['profile']);
        $this->assertEquals('Whatever you Say!!!!', $config->getOptions()['drupal']['site']['name']);
        $this->assertEquals('testing@example.com', $config->getOptions()['drupal']['account']['mail']);
        $this->assertEquals('hacker123', $config->getOptions()['drupal']['account']['name']);
        $this->assertEquals('secret', $config->getOptions()['drupal']['account']['pass']);
        $this->assertContains('Success, the options have been saved.', $output);
        $this->assertEquals('droath/project-x', $config->getOptions()['deploy']['github_repo']);
        $this->assertArrayHasKey('docker', $config->getOptions());
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
                    case 'project_version':
                        return 8;
                    case 'select_engine':
                        return 'docker';
                    case 'github_repo':
                        return 'droath/project-x';
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
                    case 'remote_name':
                        return 'Development';
                    case 'remote_path':
                        return '/var/www/html/docroot';
                    case 'remote_uri':
                        return 'dev.project-x.com';
                    case 'remote_ssh_url':
                        return 'admin@dev.project-x.com';
                    case 'remote_realm':
                        static $index = 0;

                        if ($index >= 1) {
                            return null;
                        }
                        $index++;
                        return 'stg';
                    case 'use_proxy':
                    case 'setup_host':
                    case 'setup_github':
                    case 'setup_remote':
                    case 'save_results':
                    case 'setup_network':
                    case 'setup_build_deploy':
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
