<?php

namespace Droath\ProjectX\Deploy;

use Droath\ProjectX\Exception\DeploymentRuntimeException;
use Droath\ProjectX\TaskResultTrait;
use Robo\Result;
use Symfony\Component\Console\Question\Question;

/**
 * Define GitHub deployment.
 */
class GitHubDeploy extends DeployBase
{
    use TaskResultTrait;

    /**
     * {@inheritdoc}
     */
    public function beforeDeploy()
    {
        parent::beforeDeploy();
        $this->runGitInitAdd();

        if ($this->hasGitTrackedFilesChanged()) {
            $version = $this->askBuildVersion();
            $this->getGitBuildStack()
                ->commit("Build commit for {$version}.")
                ->tag($version)
                ->run();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onDeploy()
    {
        parent::onDeploy();
        $this->getGitBuildStack()
            ->exec("push -u --tags {$this->gitOrigin()} {$this->gitBranch()}")
            ->run();
    }

    /**
     * Has build been gitified.
     *
     * @return bool
     */
    protected function buildHasGit()
    {
        return file_exists("{$this->buildPath()}/.git");
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
     * Has git tracked files changed.
     *
     * @return bool
     */
    protected function hasGitTrackedFilesChanged()
    {
        $task = $this->getGitBuildStack()
            ->exec("status --untracked-files=no --porcelain");

        /** @var Result $result */
        $result = $this->runSilentCommand($task);

        $changes = array_filter(
            explode("\n", $result->getMessage())
        );

        return (bool) count($changes) != 0;
    }

    /**
     * Ask build version.
     *
     * @return string
     */
    protected function askBuildVersion()
    {
        $last_version = $this->gitLatestVersionTag();
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
     * Run git initialize add.
     *
     * @return self
     */
    protected function runGitInitAdd()
    {
        $repo = $this->gitRepo();
        $origin = $this->gitOrigin();
        $branch = $this->gitBranch();

        $stack = $this->getGitBuildStack();
        if (!$this->buildHasGit()) {
            $stack
                ->exec('init')
                ->exec("remote add {$origin} git@github.com:{$repo}");

            if ($this->gitRemoteBranchExist()) {
                $stack
                    ->exec('fetch --all')
                    ->exec("reset --soft {$origin}/{$branch}")
                    ->checkout($branch);
            }
        } else {
            $stack
                ->checkout($branch)
                ->pull($origin, $branch);
        }
        $result = $stack
            ->add('.')
            ->run();

        $this->validateTaskResult($result);

        return $this;
    }

    /**
     * Deployment latest git version tag.
     *
     * @return string
     */
    protected function gitLatestVersionTag()
    {
        $task = $this->getGitBuildStack()
            ->exec('describe --abbrev=0 --tags');

        $result = $this->runSilentCommand($task);
        $version = trim($result->getMessage());

        return !empty($version) ? $version : '0.0.0';
    }

    /**
     * Deployment git remote branch exist.
     *
     * @return bool
     */
    protected function gitRemoteBranchExist()
    {
        $task = $this->getGitBuildStack()
            ->exec("ls-remote --exit-code --heads git@github.com:{$this->gitRepo()} {$this->gitBranch()}");

        /** @var Result $result */
        $result = $this->runSilentCommand($task);

        return $result->getExitCode() === 0;
    }

    /**
     * Deployment git branch.
     *
     * @return string
     */
    protected function gitBranch()
    {
        $options = $this->getOptions();

        return isset($options['branch'])
            ? $options['branch']
            : 'master';
    }

    /**
     * Deployment git build repo.
     *
     * @param bool $throw_exception
     *
     * @return string
     */
    protected function gitRepo($throw_exception = true)
    {
        $options = $this->getOptions();

        $repo = isset($options['github_repo'])
            ? $options['github_repo']
            : null;

        if (!isset($repo) && $throw_exception) {
            throw new DeploymentRuntimeException(
                'Missing GitHub repository in deploy options.'
            );
        }

        return $repo;
    }

    /**
     * Deployment git origin.
     *
     * @return string
     */
    protected function gitOrigin()
    {
        $options = $this->getOptions();

        return isset($options['origin'])
            ? $options['origin']
            : 'origin';
    }

    /**
     * Get Git build stack.
     *
     * @return \Robo\Task\Vcs\GitStack
     */
    protected function getGitBuildStack()
    {
        return $this->taskGitStack()->dir($this->buildPath());
    }
}
