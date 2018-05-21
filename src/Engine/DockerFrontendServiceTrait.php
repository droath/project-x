<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\ProjectX;

/**
 * Trait DockerFrontendServiceTrait
 *
 * @package Droath\ProjectX\Engine
 */
trait DockerFrontendServiceTrait
{
    /**
     * Define docker dev service.
     *
     * @return DockerService
     */
    public function devService()
    {
        return (new DockerService())
            ->setVolumes([
                'docker-sync:/var/www/html:nocopy'
            ]);
    }

    /**
     * Define docker dev volumes.
     *
     * @return array
     *   An array of docker dev volumes.
     */
    public function devVolumes()
    {
        return [
            'docker-sync' => [
                'external' => [
                    'name' => '${SYNC_NAME}-docker-sync'
                ]
            ]
        ];
    }

    /**
     * Alter the service object.
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
            $links = [];
            $name = $this->getName();
            $host = ProjectX::getProjectConfig()
                ->getHost();

            if (isset($host['name'])) {
                $links[] = "traefik.{$name}.frontend.rule=Host:{$host['name']}";
            }
            $service->setNetworks([
                'internal',
                DockerEngineType::TRAEFIK_NETWORK
            ])->setLabels(array_merge([
                'traefik.enable=true',
                "traefik.{$name}.frontend.backend={$name}",
                "traefik.{$name}.port={$this->ports()[0]}",
                'traefik.docker.network=' . DockerEngineType::TRAEFIK_NETWORK,
            ], $links));
        } else {
            $service->setPorts($this->getPorts());
        }

        return $service;
    }
}
