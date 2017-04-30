<?php

use Droath\ProjectX\Project\ProjectType;
use Droath\ProjectX\TaskSubTypeInterface;

/**
 * Define test project type.
 */
class TestProjectType extends ProjectType implements TaskSubTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getLabel()
    {
        return 'Testing Project Type';
    }

    /**
     * {@inheritdoc}
     */
    public static function getTypeId()
    {
        return 'testing_project_type';
    }
}
