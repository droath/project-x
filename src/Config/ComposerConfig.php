<?php

namespace Droath\ProjectX\Config;

/**
 * Define the composer configuration.
 */
class ComposerConfig extends JsonConfigBase
{
    public $name;
    public $description;
    public $version;
    public $type;
    public $keywords = [];
    public $homepage;
    public $time;
    public $license;
    public $authors = [];
    public $support = [];
    public $prefer_stable;
    public $minimum_stability;
    public $bin = [];
    public $config = [];
    public $require = [];
    public $require_dev = [];
    public $conflict = [];
    public $replace = [];
    public $provide = [];
    public $suggest = [];
    public $autoload = [];
    public $autoload_dev = [];
    public $repositories = [];
    public $scripts = [];
    public $extra = [];
    public $archive = [];
    public $non_feature_branches = [];

    /**
     * Define the __call method.
     *
     * @throws \RuntimeException
     */
    public function __call($name, array $arguments)
    {
        if (strpos($name, 'get') === 0
            || strpos($name, 'set') === 0) {
            $method = substr($name, 0, 3);
            $property = strtolower(
                preg_replace('/([a-z])([A-Z])/', '$1_$2', substr($name, 3))
            );

            switch ($method) {
                case 'get':
                    return $this->__get($property);

                case 'set':
                    // Only take the first argument as we don't have any logic
                    // in place to take care of more then one value being set.
                    return $this->__set($property, $arguments[0]);
            }
        }

        throw new \RuntimeException(
            sprintf('Undefined %s() method.', $name)
        );
    }

    /**
     * Define the __get method.
     */
    public function __get($property)
    {
        if (!property_exists($this, $property)) {
            throw new \RuntimeException(
                sprintf('Undefined %s property.', $property)
            );
        }

        return $this->{$property};
    }

    /**
     * Define the __set method.
     *
     * @throws \RuntimeException
     */
    public function __set($property, $value)
    {
        if (!property_exists($this, $property)) {
            throw new \RuntimeException(
                sprintf('Undefined %s property.', $property)
            );
        }

        if (empty($value)) {
            throw new \InvalidArgumentException(
                sprintf('Empty value not allowed for %s property.', $property)
            );
        }

        if (is_array($this->{$property})
            && !is_array($value)) {
            throw new \InvalidArgumentException(
                sprintr('Invalid argument provided for %s, expecting an array.', $property)
            );
        }

        $this->{$property} = $value;

        return $this;
    }

    public function addRepository($name, array $options)
    {
        if (!isset($this->repositories[$name])) {
            $this->repositories[$name] = $options;
        }

        return $this;
    }

    /**
     * Add an array of required packages.
     *
     * @param array $requires
     */
    public function addRequires(array $requires, $dev = false)
    {
        foreach ($requires as $vendor => $version) {
            if (!isset($vendor) || !isset($version)) {
                continue;
            }

            if (!$dev) {
                $this->addRequire($vendor, $version);
            } else {
                $this->addDevRequire($vendor, $version);
            }
        }

        return $this;
    }

    /**
     * Add a single require package.
     *
     * @param string $vendor
     *   The composer package vendor.
     * @param string $version
     *   The composer package version.
     */
    public function addRequire($vendor, $version)
    {
        if (!isset($this->require[$vendor])) {
            $this->require[$vendor] = $version;
        }

        return $this;
    }

    /**
     * Add a single development require package.
     *
     * @param string $vendor
     *   The composer package vendor.
     * @param string $version
     *   The composer package version.
     */
    public function addDevRequire($vendor, $version)
    {
        if (!isset($this->require_dev[$vendor])) {
            $this->require_dev[$vendor] = $version;
        }

        return $this;
    }

    /**
     * Add extra options.
     *
     * @param string $key
     *   The unique extra namespace.
     * @param array $options
     *   The extra options.
     */
    public function addExtra($key, array $options)
    {
        if (!isset($this->extra[$key])) {
            $this->extra[$key] = $options;
        }

        return $this;
    }
}
