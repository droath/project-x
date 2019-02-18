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
     * @option $include-asset Include directory/file to the build.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deployBuild($opts = [
        'build-path' => null,
        'deploy-type' => 'git',
        'include-asset' => [],
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $build_path = $this->buildPath($opts);
        $deploy_type = $opts['deploy-type'];

        if (!file_exists($build_path)) {
            $this->_mkdir($build_path);
        }
        $this->executeBuild(__FUNCTION__, $build_path);
        $this->executeIncludeAssets($opts['include-asset'], $build_path);
        $this->executeCommandHook(__FUNCTION__, 'after');

        $continue = !is_null($deploy_type)
            ? $this->doAsk(new ConfirmationQuestion('Run deployment? (y/n) [yes] ', true))
            : false;

        if (!$continue) {
            return;
        }

        $this->deployPush([
            'build-path' => $build_path,
            'deploy-type' => $deploy_type
        ]);
    }

    /**
     * Run the deployment push process.
     *
     * @param array $opts
     * @option $build-path The path that the build was built at.
     * @option $deploy-type The deployment type that should be used.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deployPush($opts = [
        'build-path' => null,
        'deploy-type' => 'git',
    ])
    {
        $this->executeCommandHook(__FUNCTION__, 'before');
        $build_path = $this->buildPath($opts);

        if (!file_exists($build_path)) {
            throw new DeploymentRuntimeException(
                'Build directory does not exist.'
            );
        }
        /** @var DeployBase $deploy */
        $deploy = $this->loadDeployTask(
            $opts['deploy-type'],
            $build_path
        );

        $this->executeDeploy(__FUNCTION__, $deploy);
        $this->executeCommandHook(__FUNCTION__, 'after');
    }

    /**
     * Execute including assets to the build directory.
     *
     * @param array $assets
     * @param $build_path
     */
    protected function executeIncludeAssets(array $assets, $build_path)
    {
        $root_path = ProjectX::projectRoot();

        foreach ($assets as $asset) {
            $file_info = new \splFileInfo("{$root_path}/{$asset}");
            if (!file_exists($file_info)) {
                continue;
            }
            $file_path = $file_info->getRealPath();
            $file_method = $file_info->isFile() ? '_copy' : '_mirrorDir';

            call_user_func_array([$this, $file_method], [
                $file_path,
                "{$build_path}/{$file_info->getFilename()}"
            ]);
        }
    }

    /**
     * Execute build process.
     *
     * @param $method
     * @param $build_path
     *
     * @return self
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function executeBuild($method, $build_path)
    {
        $this->say('Build has initialized!');
        $this->executeCommandHook($method, 'before_build');
        $this->projectInstance()->onDeployBuild($build_path);
        $this->executeCommandHook($method, 'after_build');
        $this->say('Build has completed!');

        return $this;
    }

    /**
     * Execute deployment process.
     *
     * @param $method
     * @param DeployBase $deploy
     *
     * @return self
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function executeDeploy($method, DeployBase $deploy)
    {
        $this->say('Deploy has initialized!');
        $deploy->beforeDeploy();
        $this->executeCommandHook($method, 'before_deploy');
        $deploy->onDeploy();
        $this->executeCommandHook($method, 'after_deploy');
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
            'git' => '\Droath\ProjectX\Deploy\GitDeploy',
        ];
    }
}
