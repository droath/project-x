<?php

namespace Droath\ProjectX;

use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Engine\EngineTypeInterface;

/**
 * Define environment engine trait.
 */
trait EngineTrait
{
    use TaskResultTrait;

    /**
     * Get engine type instance
     *
     * @return EngineTypeInterface
     */
    protected function getEngineInstance()
    {
        return ProjectX::getEngineType();
    }

    /**
     * Get engine type instance.
     *
     * @deprecated
     * @return EngineTypeInterface
     */
    protected function engineInstance()
    {
        return $this->getEngineInstance();
    }

    /**
     * Get environment engine options.
     *
     * @return array
     *   An array of engine options defined in the project-x configuration.
     */
    protected function getEngineOptions()
    {
        $config = ProjectX::getProjectConfig();

        $engine = $config->getEngine();
        $options = $config->getOptions();

        return isset($options[$engine])
            ? $options[$engine]
            : [];
    }

    /**
     * Get project engine service names by type.
     *
     * @param $type
     *   The service type on what to search for.
     *
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getEngineServiceNamesByType($type)
    {
        return $this->getEngineInstance()->getServiceNamesByType($type);
    }

    /**
     * Execute environment engine command.
     *
     * @param $command
     * @param null $service
     * @param array $options
     * @param bool $quiet
     * @param bool $localhost
     * @return \Robo\Result|\Robo\ResultData
     */
    protected function executeEngineCommand($command, $service = null, $options = [], $quiet = false, $localhost = false)
    {
        if ($command instanceof CommandBuilder) {
            $command = $command->build();
        }
        $engine = $this->getEngineInstance();

        if ($engine instanceof DockerEngineType && !$localhost) {
            $results = $engine->execRaw($command, $service, $options, $quiet);
        } else {
            $results = $this->_exec($command);
        }

        $this->validateTaskResult($results);

        return $results;
    }
}
