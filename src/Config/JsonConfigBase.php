<?php

namespace Droath\ProjectX\Config;

use Droath\ProjectX\Config\ConfigInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Define JSON configuration base.
 */
abstract class JsonConfigBase extends ConfigBase implements ConfigInterface
{
    const CONFIG_FORMAT = 'json';
    const CONFIG_CONTEXT = [
        'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    ];

    /**
     * {@inheritdoc}
     */
    public static function toArrayCallback()
    {
        return 'json_decode';
    }

    /**
     * {@inheritdoc}
     */
    public static function toStringCallback()
    {
        return 'json_encode';
    }

    /**
     * {@inheritdoc}
     */
    protected static function encoders()
    {
        return [
            new JsonEncoder(
                new JsonEncode()
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected static function toArrayArgs()
    {
        return [true];
    }

    /**
     * {@inheritdoc}
     */
    protected static function nameConvertClassname()
    {
        return new DashNameConverter();
    }
}
