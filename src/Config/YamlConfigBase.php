<?php

namespace Droath\ProjectX\Config;

use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;

/**
 * Define YAML based configurations.
 */
abstract class YamlConfigBase
{
    const CONTEXT_INLINE = 4;
    const CONTEXT_INDENT = 0;
    const CONTEXT_FLAGS = 0;

    /**
     * Create configuration object from string.
     *
     * @param string $string
     *
     * @return self
     */
    public static function createFromString($string) {
        return self::getSerializer()
            ->deserialize($string, get_called_class(), 'yaml');
    }

    /**
     * Create configuration object from array.
     *
     * @param array $array
     *
     * @return self
     */
    public static function createFromArray(array $array)
    {
        return self::createFromString(Yaml::dump($array));
    }

    /**
     * Create configuration object from file.
     *
     * @param \SplFileInfo $file_info
     *
     * @return self
     */
    public static function createFromFile(\SplFileInfo $file_info)
    {
        $contents = file_get_contents($file_info);

        if (!$contents) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to retrieve contents from file (%s).',
                    $file_info->getRealPath()
                )
            );
        }

        return self::createFromString($contents);
    }

    /**
     * Render config contents as array.
     *
     * @return array
     */
    public function toArray()
    {
        return Yaml::parse($this->toYaml());
    }

    /**
     * Render config contents as YAML.
     *
     * @return string
     */
    public function toYaml()
    {
        return self::getSerializer()->serialize($this, 'yaml');
    }

    /**
     * Update instance based on updated values.
     *
     * @param array $values
     *
     * @return self
     */
    public function update(array $values)
    {
        return self::createFromArray(array_replace_recursive(
            $this->toArray(),
            $values
        ));
    }

    /**
     * Save instance to filesystem.
     *
     * @param string $filename
     *   The full path to the filename.
     *
     * @return int|bool
     */
    public function save($filename)
    {
        return file_put_contents($filename, $this->toYaml());
    }

    /**
     * Get serializer object.
     *
     * @return \Symfony\Component\Serializer\Serializer
     */
    protected static function getSerializer()
    {
        $context = [
            'yaml_inline' => static::CONTEXT_INLINE,
            'yaml_indent' => static::CONTEXT_INDENT,
            'yaml_flags' => static::CONTEXT_FLAGS,
        ];

        return new Serializer(
            [new ObjectNormalizer()],
            [new YamlEncoder(null, null, $context)]
        );
    }
}
