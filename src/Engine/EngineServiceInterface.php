<?php

namespace Droath\ProjectX\Engine;

/**
 * Define service engine interface.
 */
interface EngineServiceInterface
{

    /**
     * Define default services.
     *
     * @return array
     *   An array of default services.
     */
    public function defaultServices();

    /**
     * Service configs.
     *
     * @return array
     *   An array of service variables keyed by service type.
     */
    public function serviceConfigs();
}
