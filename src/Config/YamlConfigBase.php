<?php

namespace Droath\ProjectX\Config;

use Droath\ProjectX\Config\ConfigInterface;
use Fitbug\SymfonySerializer\YamlEncoderDecoder\YamlDecode;
use Fitbug\SymfonySerializer\YamlEncoderDecoder\YamlEncode;
use Fitbug\SymfonySerializer\YamlEncoderDecoder\YamlEncoder;
use Symfony\Component\Yaml\Yaml;

/**
 * Define YAML based configurations.
 */
abstract class YamlConfigBase extends ConfigBase implements ConfigInterface
{
    const CONTEXT_INLINE = 8;
    const CONTEXT_INDENT = 2;
    const CONFIG_FORMAT = 'yaml';

    /**
     * {@inheritdoc}
     */
    public static function toArrayCallback()
    {
        return [Yaml::class, 'parse'];
    }

    /**
     * {@inheritdoc}
     */
    public static function toStringCallback()
    {
        return [Yaml::class, 'dump'];
    }

    /**
     * {@inheritdoc}
     */
    protected static function encoders()
    {
        return [
            new YamlEncoder(
                new YamlEncode(
                    false,
                    false,
                    false,
                    false,
                    static::CONTEXT_INLINE,
                    static::CONTEXT_INDENT
                ),
                new YamlDecode()
            ),
        ];
    }
}
