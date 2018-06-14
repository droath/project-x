<?php

namespace Droath\ProjectX\Platform;

use Droath\ProjectX\TaskSubTypeInterface;

/**
 * Define a NULL platform.
 */
class NullPlatformType extends PlatformType implements TaskSubTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getLabel()
    {
        return 'Null';
    }

    /**
     * {@inheritdoc}
     */
    public static function getTypeId()
    {
        return 'null';
    }
}
