<?php

namespace Droath\ProjectX\Deploy;

use Droath\ProjectX\Exception\DeploymentRuntimeException;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskCommonTrait;
use Robo\Tasks;

/**
 * Define deployment base class.
 *
 * @package Droath\ProjectX\Deploy
 */
abstract class DeployBase extends Tasks
{
    use TaskCommonTrait;

    /**
     * Deployment build path.
     *
     * @var string
     */
    protected $buildPath;

    /**
     * Deployment configurations.
     *
     * @var array
     */
    protected $configurations;

    /**
     * Deployment failed indication.
     *
     * @var bool
     */
    protected $deployStop = false;

    /**
     * Deployment base class constructor.
     *
     * @param $build_path
     * @param array $configurations
     */
    public function __construct($build_path, array $configurations = [])
    {
        $this->buildPath = $build_path;
        $this->configurations = $configurations;
    }

    /**
     * Executes the main deployment process.
     */
    public function onDeploy()
    {
        $this->validNextDeployStep();
    }

    /**
     * React before deploy process has initialized.
     */
    public function beforeDeploy()
    {
        $this->validNextDeployStep();
    }

    /**
     * React after deploy process has completed.
     */
    public function afterDeploy()
    {
        $this->validNextDeployStep();
    }

    /**
     * Stop the deployment process.
     */
    protected function stopDeploy()
    {
        $this->deployStop = true;

        return $this;
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
            throw new DeploymentRuntimeException(
                'Unable to increment semantic version.'
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
     * Valid next deployment step should start.
     */
    protected function validNextDeployStep()
    {
        if ($this->deployStop) {
            throw new DeploymentRuntimeException(
                'Deployment process has been stopped.'
            );
        }
    }

    /**
     * Get deployment options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = ProjectX::getProjectConfig()->getOptions();
        return isset($options['deploy'])
            ? array_map('trim', $options['deploy'])
            : [];
    }

    /**
     * Deployment build path.
     *
     * @return string
     */
    protected function buildPath()
    {
        return $this->buildPath;
    }

    /**
     * Deployment configurations
     *
     * @return array
     */
    protected function configurations()
    {
        return $this->configurations;
    }
}
