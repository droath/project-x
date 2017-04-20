<?php

namespace Droath\ProjectX\Config;

use Droath\ProjectX\Config\ConfigObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Define configuration base.
 */
abstract class ConfigBase
{
    const CONFIG_CONTEXT = [];

    /**
     * Create configuration object from array.
     *
     * @param array $array
     *
     * @return self
     */
    public static function createFromArray(array $array)
    {
        return self::createFromString(self::toString($array));
    }

    /**
     * Create configuration object from string.
     *
     * @param string $string
     *
     * @return self
     */
    public static function createFromString($string)
    {
        return self::getSerializer()
            ->deserialize($string, get_called_class(), static::CONFIG_FORMAT, static::CONFIG_CONTEXT);
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
     * Update instance based on updated values.
     *
     * @param array $values
     *   An array of values to update an instance.
     *
     * @return self
     */
    public function update(array $values)
    {
        return self::createFromArray(array_replace_recursive(
            static::toArray(),
            $values
        ));
    }

    /**
     * Save serialized instance to the filesystem.
     *
     * @param string $filename
     *   The full path to the filename.
     *
     * @return int|bool
     */
    public function save($filename)
    {
        return file_put_contents($filename, $this->toFormat());
    }

    /**
     * Render config contents as YAML.
     *
     * @return string
     */
    public function toFormat()
    {
        return self::getSerializer()->serialize($this, static::CONFIG_FORMAT, static::CONFIG_CONTEXT);
    }

    /**
     * Render config contents as array.
     *
     * @return array
     */
    public function toArray()
    {
        return call_user_func_array(
            static::toArrayCallback(), array_merge([$this->toFormat()], static::toArrayArgs())
        );
    }

    /**
     * Convert config array into a formatted string.
     *
     * @param array $array
     *   An array of data to render a string.
     *
     * @return string
     */
    protected static function toString(array $array)
    {
        return call_user_func_array(
            static::toStringCallback(), array_merge([$array], static::toStringArgs())
        );
    }

    /**
     * Serializer normalizers.
     */
    protected static function normalizers()
    {
        return [
            new ConfigObjectNormalizer(
                static::metadataFactoryClassname(),
                static::nameConvertClassname(),
                static::propertyAccessorClassname()
            ),
        ];
    }


    /**
     * Get serializer object.
     *
     * @return \Symfony\Component\Serializer\Serializer
     */
    protected static function getSerializer()
    {
        return new Serializer(
            static::normalizers(),
            static::encoders()
        );
    }

    /**
     * Set toArrayCallback() arguments
     *
     * @return array
     */
    protected static function toArrayArgs()
    {
        return [];
    }

    /**
     * Set toStringCallback() arguments
     *
     * @return array
     */
    protected static function toStringArgs()
    {
        return [];
    }

    /**
     * Set name convert classname.
     *
     * @return null|object
     */
    protected static function nameConvertClassname()
    {
        return null;
    }

    /**
     * Set metadata factory classname.
     *
     * @return null|object
     */
    protected static function metadataFactoryClassname()
    {
        return null;
    }

    /**
     * Set property accessor classname.
     *
     * @return null|object
     */
    protected static function propertyAccessorClassname()
    {
        return null;
    }

    /**
     * Define to array callback.
     *
     * @return array|string
     *   An array or string that's passed along to the call_user_func_array().
     */
    abstract public static function toArrayCallback();

    /**
     * Define to string callback.
     *
     * @return array|string
     *   An array or string that's passed along to the call_user_func_array().
     */
    abstract public static function toStringCallback();

    /**
     * Serializer encoders.
     *
     * @return array
     */
    abstract protected static function encoders();
}
