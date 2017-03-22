<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\ProjectXAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\LoadAllTasks;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Define Project-X project type.
 */
abstract class ProjectType implements BuilderAwareInterface, ContainerAwareInterface, IOAwareInterface
{
    /**
     * Project install root.
     */
    const INSTALL_ROOT = '/docroot';

    /**
     * Project type supports docker.
     *
     * @var bool
     */
    protected $supportsDocker = false;

    use IO;
    use LoadAllTasks;
    use ContainerAwareTrait;
    use ProjectXAwareTrait;

    /**
     * Project supports docker.
     *
     * @return self
     */
    public function supportsDocker()
    {
        $this->supportsDocker = true;

        return $this;
    }

    /**
     * Has project docker support.
     */
    public function hasDockerSupport()
    {
        return $this->supportsDocker;
    }

    /**
     * React on the engine startup.
     */
    public function onEngineUp()
    {
        // Nothing to do at the parent level.
    }

    /**
     * React on the engine shutdown.
     */
    public function onEngineDown()
    {
        // Nothing to do at the parent level.
    }

    /**
     * Copy template file to project root.
     *
     * @param string $filename
     *   The filename of template file.
     * @param bool $overwrite
     *   A flag to determine if the file should be overwritten if exists.
     */
    protected function copyTemplateFileToProject($filename, $overwrite = false)
    {
        $this->copyTemplateFilesToProject([$filename => $filename], $overwrite);
    }

    /**
     * Copy template files to project root.
     *
     * @param array $filenames
     *   An array of template filenames, keyed by target path.
     * @param bool $overwrite
     *   A flag to determine if the file should be overwritten if exists.
     */
    protected function copyTemplateFilesToProject(array $filenames, $overwrite = false)
    {
        try {
            $filesystem = $this->taskFilesystemStack();
            foreach ($filenames as $template_path => $target_path) {
                $target_file = $this
                    ->getProjectXRootPath() . "/{$target_path}";

                $template_file = $this
                    ->templateManager()
                    ->getTemplateFilePath($template_path);

                $filesystem->copy($template_file, $target_file, $overwrite);
            }
            $filesystem->run();
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf('Failed to copy template file(s) into project!')
            );
        }
    }

    /**
     * Ask confirmation question.
     *
     * @param string $text
     *   The question text.
     * @param bool $default
     *   The default value.
     *
     * @return bool
     */
    protected function askConfirmQuestion($text, $default = false)
    {
        $default_text = $default ? 'yes' : 'no';
        $question = "☝️  $text (y/n) [$default_text] ";

        return $this->doAsk(new ConfirmationQuestion($question, $default));
    }

    /**
     * Get application command.
     *
     * @param string $name
     *   The name of the command.
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function getAppCommand($name)
    {
        return $this->getApplication()->find($name);
    }

    /**
     * Template manager instance.
     *
     * @return \Droath\ProjectX\Template\TemplateManager
     */
    protected function templateManager()
    {
        return $this->getContainer()->get('projectXTemplate');
    }

    /**
     * Get console application.
     *
     * @return \Symfony\Component\Console\Application
     */
    protected function getApplication()
    {
        return $this->getContainer()->get('application');
    }

    /**
     * Get host checker service.
     *
     * @return \Droath\ProjectX\Service\HostChecker
     *   The host check object.
     */
    protected function getHostChecker()
    {
        return $this->getContainer()->get('projectXHostChecker');
    }

    /**
     * Get project hostname.
     *
     * @return string
     *   The project hostname defined in project-x config; otherwise localhost.
     */
    protected function getProjectHostname()
    {
        $config = $this->getProjectXConfig();

        return isset($config['host']) ? $config['host']['name'] : 'localhost';
    }

    /**
     * Get template directory path for the given file.
     *
     * @return string
     *   The full path to the template file.
     */
    protected function getTemplateFilePath($filename)
    {
        return $this->templateManager()->getTemplateFilePath($filename);
    }
}
