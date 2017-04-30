<?php

use Droath\ProjectX\Engine\EngineType;
use Droath\ProjectX\TaskSubTypeInterface;

/**
 * Define test engine type.
 */
class TestEngineType extends EngineType implements TaskSubTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getLabel()
    {
        return 'Testing Engine Type';
    }

    /**
     * {@inheritdoc}
     */
    public static function getTypeId()
    {
        return 'testing_engine_type';
    }
}
