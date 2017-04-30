<?php

namespace Droath\ProjectX\Engine;

use Droath\ProjectX\Engine\EngineType;
use Droath\ProjectX\TaskSubTypeInterface;

/**
 * Define null engine type.
 */
class NullEngineType extends EngineType implements TaskSubTypeInterface
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
