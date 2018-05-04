<?php

namespace Droath\ProjectX\Config;

use Droath\ProjectX\Engine\DockerService;

/**
 * Class DockerComposeConfig
 *
 * @package Droath\ProjectX\Config
 */
class DockerComposeConfig extends YamlConfigBase
{
    public $version;
    public $services = [];
    public $volumes = [];
    public $networks = [];

    /**
     * Docker compose version.
     *
     * @param $version
     *   The docker compose version.
     *
     * @return self
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set docker compose networks.
     *
     * @param array $networks
     *
     * @return $this
     */
    public function setNetworks(array $networks)
    {
        $this->networks = $networks;

        return $this;
    }

    /**
     * Set docker compose service.
     *
     * @param $name
     *   The docker service name.
     * @param DockerService $service
     *   The docker service object.
     *
     * @return self
     */
    public function setService($name, DockerService $service)
    {
        $this->services[$name] = $service;

        return $this;
    }

    /**
     * Set docker compose volumes.
     *
     * @param array $volumes
     *
     * @return self
     */
    public function setVolumes(array $volumes)
    {
        $this->volumes = $volumes;

        return $this;
    }
}
