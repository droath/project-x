<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\EngineTypeInterface;
use Droath\ProjectX\ProjectX;

/**
 * Define the docker service base class.
 */
abstract class DockerServiceBase
{
    /**
     * Docker service default version.
     */
    const DEFAULT_VERSION = 'latest';

    /**
     * Docker service allowed variables.
     */
    const DOCKER_VARIABLES = [];

    /**
     * Docker service properties override.
     */
    const PROPERTIES_OVERRIDE = [
        'ports',
        'links',
        'environment'
    ];

    /**
     * The docker service name.
     *
     * @var string
     */
    protected $name;

    /**
     * The service version.
     *
     * @var string
     */
    protected $version;

    /**
     * The engine type.
     *
     * @var EngineTypeInterface
     */
    protected $engine;

    /**
     * The docker service.
     *
     * @var DockerService
     */
    protected $service;

    /**
     * The docker service configs
     *
     * @var array
     */
    protected $configs = [];

    /**
     * Bind ports to host.
     *
     * @var bool
     */
    protected $bindPorts = false;

    /**
     * The docker service internal flag.
     *
     * @var bool
     */
    protected $internal = false;

    /**
     * Docker service base.
     *
     * @param EngineTypeInterface $engine
     *   The current engine type.
     * @param string|null $name
     *   The service machine name.
     */
    public function __construct(EngineTypeInterface $engine, $name = null)
    {
        $this->name = $name;
        $this->engine = $engine;
    }

    /**
     * Docker service groups.
     *
     * @return string
     */
    public static function group()
    {
        return 'service';
    }

    /**
     * Set docker service version.
     *
     * @param string $version
     *   The service version.
     *
     * @return self
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set the docker service configs.
     *
     * @param array $configs
     *   An array of configurations.
     *
     * @return $this
     */
    public function setConfigs(array $configs)
    {
        $this->configs = $configs;

        return $this;
    }

    /**
     * Set docker service as internal.
     *
     * @return $this
     */
    public function setInternal()
    {
        $this->internal = true;

        return $this;
    }

    /**
     * Get docker service version.
     *
     * @return int|string
     */
    public function getVersion()
    {
        return isset($this->version)
            ? $this->version
            : static::DEFAULT_VERSION;
    }

    /**
     * Bind service ports to host.
     *
     * @return $this
     */
    public function bindPorts()
    {
        $this->bindPorts = true;

        return $this;
    }

    /**
     * Docker service ports.
     *
     * @return array
     *   An array of service ports.
     */
    public function ports()
    {
        return [];
    }

    /**
     * Docker service volumes.
     *
     * @return array
     *   An array of service volumes.
     */
    public function volumes()
    {
        return [];
    }

    /**
     * Docker dev service volumes.
     *
     * @return array
     *   An array of dev service volumes.
     */
    public function devVolumes()
    {
        return [];
    }

    /**
     * Docker dev service blueprint.
     *
     * @return DockerService
     */
    public function devService()
    {
        return new DockerService();
    }

    /**
     * Docker service template files.
     *
     * @return array
     *   The template files related to the service.
     */
    public function templateFiles()
    {
        return [];
    }

    /**
     * Get docker service name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get complete service object.
     *
     * @return \Droath\ProjectX\Engine\DockerService
     *   A fully defined service object.
     */
    public function getService()
    {
        if (!isset($this->service)) {
            $info = $this->getInfo();
            $this->service = $this->service();

            // Apply the overridden property values.
            foreach (static::PROPERTIES_OVERRIDE as $property) {
                if (!isset($info[$property]) || empty($info[$property])) {
                    continue;
                }
                $method = 'set' . ucwords($property);

                if (is_callable([$this->service, $method])) {
                    call_user_func_array([$this->service, $method], [$info[$property]]);
                }
            }
        }

        return $this->service;
    }

    /**
     * Get environment value.
     *
     * @param $name
     *   The name of the environment variable.
     *
     * @return string
     *   The environment value; otherwise null.
     */
    public function getEnvironmentValue($name)
    {
        $name = strtolower($name);
        $service = $this->getService();

        foreach ($service->getEnvironment() as $environment) {
            list($key, $value) = explode('=', $environment);
            if (strtolower($key) !== $name) {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * Get Docker host ports.
     *
     * @return array
     *   An array of host ports.
     */
    public function getHostPorts()
    {
        $ports = [];
        $service = $this->getService();

        foreach ($service->getPorts() as $port) {
            list($host,) = explode(':', $port);
            $ports[] = $host;
        }

        return $ports;
    }

    /**
     * Get Docker formatted service ports.
     *
     * @param null $host
     *
     * @return array
     *   An array of Docker service ports.
     */
    protected function getPorts($host = null)
    {
        $ports = $this->ports();
        array_walk($ports, function (&$port) use ($host) {
            $host = isset($host) ? $host : $port;
            $port = "{$host}:{$port}";
        });

        return $ports;
    }

    /**
     * Get all defined docker service names.
     *
     * @return array
     *   An array of docker service types.
     */
    protected function getServiceNames()
    {
        $names = [];

        foreach ($this->getServices() as $name => $info) {
            if (!isset($info['type'])) {
                continue;
            }
            $names[$name] = $info['type'];
        }

        return $names;
    }

    /**
     * Get docker service name by type.
     *
     * @param $type
     *   The service type.
     *
     * @return false|int|string
     *   The docker service container name.
     */
    protected function getServiceNameByType($type)
    {
        return array_search($type, $this->getServiceNames());
    }

    /**
     * Default alternations to the service object.
     *
     * @param DockerService $service
     *   The docker service object.
     *
     * @return DockerService
     *   The alter service.
     */
    protected function alterService(DockerService $service)
    {
        if ($this->internal) {
            $service
                ->setNetworks([
                    'internal'
                ])
                ->setlabels([
                    'traefik.enable=false'
                ]);

            if ($this->bindPorts) {
                $service->setPorts(
                    $this->getPorts('0000')
                );
            }
        } else {
            $service->setPorts($this->getPorts());
        }

        return $service;
    }

    /**
     * Format command into a docker run statement.
     *
     * @param array $commands
     * @return string
     */
    protected function formatRunCommand(array $commands)
    {
        $last = end($commands);

        return 'RUN ' . implode("\r\n  && ", array_map(
            function ($value) use ($last) {
                return $last !== $value ? "{$value} \\" : $value;
            },
            $commands
        ));
    }

    /**
     * Format an array into a string delimiter.
     *
     * @param array $values
     * @param string $delimiter
     * @return string
     */
    protected function formatValueDelimiter(array $values, $delimiter = ' ')
    {
        return implode($delimiter, $values);
    }

    /**
     * Merge configuration variables.
     *
     * @param $name
     *   The name of variable.
     * @param array $values
     *   An array of values to merge into the configuration.
     *
     * @return $this
     */
    protected function mergeConfigVariable($name, array $values)
    {
        if (in_array($name, static::DOCKER_VARIABLES)) {
            $this->configs[$name] = array_unique(
                array_merge($this->configs[$name], $values)
            );
        }

        return $this;
    }

    /**
     * Get information about service.
     *
     * @return array
     *   An array of service information defined in project-x configuration.
     */
    protected function getInfo()
    {
        foreach ($this->getServices() as $info) {
            if ($info['type'] === static::name()) {
                return $info;
            }
        }

        return [];
    }

    /**
     * Get info property value.
     *
     * @param $name
     *   The property name.
     * @param array $default
     *   A default value to return if non-existent.
     *
     * @return array|mixed
     */
    protected function getInfoProperty($name, $default = [])
    {
        $info = $this->getInfo();
        return isset($info[$name]) ? $info[$name] : $default;
    }

    /**
     * Get engine services definitions.
     *
     * @return array
     *   An array of service definitions defined by the engine.
     */
    protected function getServices()
    {
        return $this->engine->getServices();
    }

    /**
     * Get project type defined in project-x configuration.
     *
     * @return string
     */
    protected function getProjectType()
    {
        return ProjectX::getProjectConfig()->getType();
    }
}
