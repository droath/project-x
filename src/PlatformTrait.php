<?php

namespace Droath\ProjectX;

use Droath\ProjectX\Platform\PlatformTypeInterface;

/**
 * Define platform trait.
 */
trait PlatformTrait
{
    /**
     * Get platform instance object.
     *
     * @return PlatformTypeInterface
     */
    protected function getPlatformInstance()
    {
        return ProjectX::getPlatformType();
    }
}
