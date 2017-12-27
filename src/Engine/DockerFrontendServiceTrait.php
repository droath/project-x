<?php

namespace Droath\ProjectX\Engine;

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
}
