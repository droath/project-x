<?php

namespace Droath\ProjectX\Platform;

use Droath\ProjectX\TaskSubTypeResolver;

/**
 * Define platform type resolver.
 */
class PlatformTypeResolver extends TaskSubTypeResolver
{
    const DEFAULT_CLASSNAME = NullPlatformType::class;

    /**
     * Define task subtype types.
     *
     * @return array
     *   An array of task subtype types.
     */
    public function types()
    {
        return $this->getPluginTypes('*PlatformType.php', 'Platform');
    }
}
