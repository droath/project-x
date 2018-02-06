<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\Deploy\DeployBase;
use Droath\ProjectX\Exception\DeploymentRuntimeException;
use Droath\ProjectX\ProjectX;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Define the deployment tasks.
 */
class DeployTasks extends TaskBase
{
    /**
     * Run the deployment build process.
     *
     * @param array $opts
     * @option $build-path The build path it should be built at.
     * @option $deploy-type The deployment type that should be used.
     */
    public function deployBuild($opts = [
        'build-path' => null,
        'deploy-type' => 'github',
    ])
    {
        $build_path = $this->buildPath($opts);
        $deploy_type = $opts['deploy-type'];

        if (!file_exists($build_path)) {
            $this->_mkdir($build_path);
        }
        $this->runBuild($build_path);

        $continue = !is_null($deploy_type)
            ? $this->doAsk(new ConfirmationQuestion('Run deployment? (y/n) [yes] ', true))
            : false;

        if (!$continue) {
            return;
        }

        /** @var DeployBase $deploy */
        $deploy = $this->loadDeployTask($deploy_type, $build_path);

        $this->runDeploy($deploy);
    }

    /**
     * Run the deployment push process.
     *
     * @param array $opts
     * @option $build-path The path that the build was built at.
     * @option $deploy-type The deployment type that should be used.
     */
    public function deployPush($opts = [
        'build-path' => null,
        'deploy-type' => 'github',
    ])
    {
        $build_path = $this->buildPath($opts);

        if (!file_exists($build_path)) {
            throw new DeploymentRuntimeException(
                'Build directory does not exist.'
            );
        }
        $deploy_type = $opts['deploy-type'];

        /** @var DeployBase $deploy */
        $deploy = $this->loadDeployTask($deploy_type, $build_path);

        $this->runDeploy($deploy);
    }

    /**
     * Run build process.
     *
     * @param $build_path
     *
     * @return self
     */
    protected function runBuild($build_path)
    {
        $this->say('Build has initialized!');
        $this->projectInstance()->onDeployBuild($build_path);
        $this->say('Build has completed!');

        return $this;
    }

    /**
     * Run deployment.
     *
     * @param DeployBase $deploy
     *
     * @return self
     */
    protected function runDeploy(DeployBase $deploy)
    {
        $this->say('Deploy has initialized!');
        $deploy->beforeDeploy();
        $deploy->onDeploy();
        $deploy->afterDeploy();
        $this->say('Deploy has completed!');

        return $this;
    }

    /**
     * Load deployment task.
     *
     * @param $type
     * @param $build_path
     * @param array $configurations
     *
     * @return DeployBase
     */
    protected function loadDeployTask($type, $build_path, array $configurations = [])
    {
        $definitions = $this->deployDefinitions();

        $classname = isset($definitions[$type]) && class_exists($definitions[$type])
                ? $definitions[$type]
                : 'Droath\ProjectX\Deploy\NullDeploy';

        return (new $classname($build_path, $configurations))
            ->setInput($this->input())
            ->setOutput($this->output())
            ->setBuilder($this->getBuilder())
            ->setContainer($this->getContainer());
    }

    /**
     * Development build path.
     *
     * @param $options
     *
     * @return string
     */
    protected function buildPath($options)
    {
        return isset($options['build-path'])
            ? $options['build-path']
            : ProjectX::buildRoot();
    }

    /**
     * Define deployment definitions.
     *
     * @return array
     */
    protected function deployDefinitions()
    {
        return [
            'github' => '\Droath\ProjectX\Deploy\GitHubDeploy',
        ];
    }
}
