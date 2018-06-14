<?php

namespace Droath\ProjectX\Platform;

use Droath\ProjectX\EngineTrait;
use Droath\ProjectX\Project\ProjectTypeInterface;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\TaskSubType;

/**
 * Define the base platform type.
 */
abstract class PlatformType extends TaskSubType
{
    use EngineTrait;

    /**
     * Get platform options.
     *
     * @return array
     */
    protected function getPlatformOptions()
    {
        $config = $this->getConfigs();
        $options = $config->getOptions();
        $platform = $config->getPlatform();

        return isset($options[$platform])
            ? $options[$platform]
            : [];
    }

    /**
     * Get project instance.
     *
     * @return ProjectTypeInterface
     */
    protected function getProjectInstance()
    {
        return ProjectX::getProjectType()
            ->setBuilder($this->getBuilder());
    }
}
