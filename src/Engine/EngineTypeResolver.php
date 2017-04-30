<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\Engine\DockerEngineType;
use Droath\ProjectX\Engine\NullEngineType;
use Droath\ProjectX\TaskSubTypeResolver;

/**
 * Resolve engine type classname.
 */
class EngineTypeResolver extends TaskSubTypeResolver
{
    const DEFAULT_CLASSNAME = NullEngineType::class;

    /**
     * {@inheritdoc}
     */
    public function types()
    {
        return [
            DockerEngineType::getTypeId() => DockerEngineType::class,
        ] + $this->getPluginTypes('*EngineType.php', 'Engine');
    }
}
