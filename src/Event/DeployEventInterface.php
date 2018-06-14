<?php

namespace Droath\ProjectX\Event;

/**
 * Define the deploy event interface.
 */
interface DeployEventInterface
{
    /**
     * @param $build_root
     * @return mixed
     */
    public function onDeployBuild($build_root);
}
