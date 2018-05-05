<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\ProjectX;

/**
 * Define the docker service base class.
 */
abstract class DockerServiceBase
{
    const DEFAULT_VERSION = 'latest';
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
     * The docker service.
     *
     * @var DockerService
     */
    protected $service;

    /**
     * The docker service internal flag.
     *
     * @var bool
     */
    protected $internal = false;

    /**
     * Docker service base.
     *
     * @param string|null $name
     *   The service machine name.
     */
    public function __construct($name = null)
    {
        $this->name = $name;
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
     * @return array
     *   An array of Docker service ports.
     */
    protected function getPorts()
    {
        $ports = $this->ports();
        array_walk($ports, function (&$port) {
            $port = "{$port}:{$port}";
        });

        return $ports;
    }

    /**
     * Get the service link types
     *
     * @return array
     *   An array of link service types.
     */
    protected function getServiceLinkTypes()
    {
        $types = [];
        $services = $this->getServices();

        foreach ($this->getInfoProperty('links') as $name) {
            if (!isset($services[$name])) {
                continue;
            }
            $service = $services[$name];
            $types[$service['type']] = $name;
        }

        return $types;
    }

    /**
     * Get docker service link name by type.
     *
     * @param $type
     *   The service type.
     *
     * @return string
     *   The docker service name for given service type.
     */
    protected function getLinkServiceNameByType($type)
    {
        $types = $this->getServiceLinkTypes();
        return isset($types[$type]) ? $types[$type] : null;
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
        } else {
            $service->setPorts($this->getPorts());
        }

        return $service;
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
     * Get all service definitions.
     *
     * @return array
     *   An array of service definitions defined in project-x configuration.
     */
    protected function getServices()
    {
        $options = ProjectX::getProjectConfig()
            ->getOptions();

        if (!isset($options['docker'])) {
            return [];
        }
        $docker = $options['docker'];

        if (!isset($docker['services'])) {
            return [];
        }

        return $docker['services'];
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
