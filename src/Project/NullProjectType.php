<?php

namespace Droath\ProjectX\Project;

use Droath\ProjectX\Project\ProjectType;
use Droath\ProjectX\TaskSubTypeInterface;

/**
 * Define null project type.
 */
class NullProjectType extends ProjectType implements TaskSubTypeInterface
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
