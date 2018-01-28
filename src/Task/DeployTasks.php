<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\Project\NullProjectType;
use Droath\ProjectX\ProjectX;
use Droath\RoboGitHub\Task\loadTasks as githubTasks;
use Robo\Contract\TaskInterface;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Task\Filesystem\loadTasks as filesystemTasks;
use Robo\Task\Vcs\GitStack;
use Symfony\Component\Console\Question\Question;

/**
 * Define the deployment tasks.
 */
class DeployTasks extends TaskBase
{
    use githubTasks;
    use filesystemTasks;

    /**
     * The deploy build path.
     *
     * @var string
     */
    protected $build_path;

    /**
     * Run the deployment build for the project.
     *
     * @param array $opts
     * @option $build-path The build path it should be built at.
     */
    public function deployBuild($opts = [
        'build-path' => null
    ])
    {
        $this->build_path = isset($opts['build-path'])
            ? $opts['build-path']
            : ProjectX::buildRoot();

        $install_root = "{$this->build_path}{$this->getInstallRoot()}";

        if (!file_exists($this->build_path)) {
            $this->_mkdir($this->build_path);
        }
        $this->runGitBuildDeploySetup();

        if (!file_exists($install_root)) {
            $this->_mkdir($install_root);
        }
        $this->projectInstance()->onDeployBuild($this->build_path);

        $status = $this->runGitBuildDeployCommit();

        $message = false === $status
            ? "Deployment build has completed.\n\nThe build wasn't deployed as no changes were detected."
            : "Deployment build has completed.\n\nThe build has been committed and deployed to GitHub.";

        $this->say($message);
    }

    /**
     * Run the Git build deploy setup.
     *
     * @param $branch_name
     * @param string $origin
     *
     * @return \Robo\Result
     */
    protected function runGitBuildDeploySetup($branch_name = 'master', $origin = 'origin')
    {
        $deploy_options = $this->getDeployOptions();

        if (!isset($deploy_options['github_repo'])) {
            throw new \RuntimeException(
                'Missing GitHub repository in deploy options.'
            );
        }
        $repo = $deploy_options['github_repo'];
        $stack = $this->getGitBuildStack();

        if ($this->buildHasGit()) {
            if (!$this->hasGitBranch($branch_name)) {
                $stack->exec("branch {$branch_name}");
            }
            $stack
                ->exec("checkout {$branch_name}")
                ->pull($origin, $branch_name);
        } else {
            $stack->exec("clone git@github.com:{$repo} {$this->build_path}");
        }

        return $stack->run();
    }

    /**
     * Run Git build deploy commit.
     *
     * @param $branch_name
     * @param string $origin
     *
     * @return \Robo\Result|bool
     */
    protected function runGitBuildDeployCommit($branch_name = 'master', $origin = 'origin')
    {
        if (!$this->hasGitTrackedFilesChanged()) {
            return false;
        }
        $stack = $this->getGitBuildStack();
        $build_version = $this->askBuildVersion();

        $stack
            ->add('.')
            ->commit("Build commit for {$build_version}.")
            ->tag($build_version)
            ->exec("push -u --tags {$origin} {$branch_name}");

        return $stack->run();
    }

    /**
     * Get Project-x deploy options.
     *
     * @return array
     */
    protected function getDeployOptions()
    {
        $options = ProjectX::getProjectConfig()->getOptions();
        return isset($options['deploy'])
            ? array_map('trim', $options['deploy'])
            : [];
    }

    /**
     * Get git build stack task object.
     *
     * @return GitStack
     */
    protected function getGitBuildStack()
    {
        return $this->taskGitStack()->dir($this->build_path);
    }

    /**
     * Get the latest git version tag.
     *
     * @return string
     */
    protected function getGitLatestVersionTag()
    {
        $task = $this->getGitBuildStack()
            ->exec('describe --abbrev=0 --tags');

        $result = $this->runSilentCommand($task);
        $version = trim($result->getMessage());

        return !empty($version) ? $version : '0.0.0';
    }

    /**
     * Has git tracked files changed.
     *
     * @return bool
     */
    protected function hasGitTrackedFilesChanged()
    {
        $task = $this->getGitBuildStack()
            ->exec("status --porcelain");

        $result = $this->runSilentCommand($task);

        $changes = array_filter(
            explode("\n", $result->getMessage())
        );

        return (bool) count($changes) != 0;
    }

    /**
     * Has git branch.
     *
     * @param $branch_name
     *
     * @return bool
     */
    protected function hasGitBranch($branch_name)
    {
        $task = $this->getGitBuildStack()
            ->exec("rev-parse -q --verify {$branch_name}");

        $result = $this->runSilentCommand($task);

        return !empty($result->getMessage());
    }

    /**
     * Has build been gitified.
     *
     * @return bool
     */
    protected function buildHasGit()
    {
        return file_exists("{$this->build_path}/.git");
    }

    /**
     * Compute if version is numeric.
     *
     * @param $version
     *
     * @return bool
     */
    protected function isVersionNumeric($version)
    {
        foreach (explode('.', $version) as $part) {
            if (!is_numeric($part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ask build version.
     *
     * @return string
     */
    protected function askBuildVersion()
    {
        $last_version = $this->getGitLatestVersionTag();
        $next_version = $this->incrementVersion($last_version);

        $question = (new Question("Set build version [{$next_version}]: ", $next_version))
            ->setValidator(function ($input_version) use ($last_version) {
                $input_version = trim($input_version);

                if (version_compare($input_version, $last_version, '==')) {
                    throw new \RuntimeException(
                        'Build version has already been used.'
                    );
                }

                if (!$this->isVersionNumeric($input_version)) {
                    throw new \RuntimeException(
                        'Build version is not numeric.'
                    );
                }

                return $input_version;
            });

        return $this->doAsk($question);
    }

    /**
     * Increment the semantic version number.
     *
     * @param $version
     * @param int $patch_limit
     * @param int $minor_limit
     *
     * @return string
     */
    protected function incrementVersion($version, $patch_limit = 20, $minor_limit = 50)
    {
        if (!$this->isVersionNumeric($version)) {
            throw new \RuntimeException(
                'Unable to increment version.'
            );
        }
        list($major, $minor, $patch) = explode('.', $version);

        if ($patch < $patch_limit) {
            ++$patch;
        } else if ($minor < $minor_limit) {
            ++$minor;
            $patch = 0;
        } else {
            ++$major;
            $patch = 0;
            $minor = 0;
        }

        return "{$major}.{$minor}.{$patch}";
    }

    /**
     * Run silent command.
     */
    protected function runSilentCommand(TaskInterface $task)
    {
        return $task->printOutput(false)
            // This is weird as you would expect this to give you more
            // information, but it suppresses the exit code from display.
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
            ->run();
    }

    /**
     * Get project install root.
     *
     * @return string
     */
    protected function getInstallRoot()
    {
        $classname = $this->getProjectClassname();
        return !is_null($classname)
            ? $classname::INSTALL_ROOT
            : NullProjectType::INSTALL_ROOT;
    }

    /**
     * Get project type classname.
     *
     * @return string|null
     */
    protected function getProjectClassname()
    {
        $instance = $this->projectInstance();
        return is_object($instance) ? get_class($instance) : null;
    }
}
