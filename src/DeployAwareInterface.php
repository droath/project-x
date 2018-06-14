<?php

namespace Droath\ProjectX;

/**
 * Deployment aware interface.
 */
interface DeployAwareInterface
{
    /**
     * React on the deploy build.
     *
     * @param $build_root
     *   The build root directory.
     */
    public function onDeployBuild($build_root);
}
