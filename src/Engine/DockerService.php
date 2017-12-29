<?php

namespace Droath\ProjectX\Engine;

/**
 * Class DockerService
 *
 * @package Droath\ProjectX\Engine
 */
class DockerService
{
    public $image;
    public $build;
    public $expose = [];
    public $links = [];
    public $env_file = [];
    public $depends_on = [];
    public $ports = [];
    public $environment = [];
    public $volumes = [];

    /**
     * Define the __call method.
     *
     * @param string $name
     *   The method name.
     * @param array $arguments
     *   The method arguments.
     *
     * @throws \RuntimeException
     *
     * @return self
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

        return $this;
    }

    /**
     * Define the __set method.
     *
     * @param $property
     *   The method property.
     * @param $value
     *   The method set value.
     *
     * @return self
     */
    public function __set($property, $value)
    {
        $this->{$property} = $value;

        return $this;
    }

    /**
     * Define the __get method.
     *
     * @param string $property
     *   The property name.
     *
     * @throws \RuntimeException
     *
     * @return string
     *   The class property value.
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
     * Set Docker image name.
     *
     * @param $name
     *   The docker service.
     * @param mixed $version
     *   The docker service version.
     *
     * @return self
     */
    public function setImage($name, $version = 'latest')
    {
        $this->image = "{$name}:{$version}";

        return $this;
    }

    /**
     * Determine if docker service is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        foreach (get_object_vars($this) as $value) {
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get an array representation of the object.
     *
     * @return array
     *   An array of all properties that have values.
     */
    public function asArray()
    {
        $array = [];
        foreach(get_object_vars($this) as $property => $value) {
            if (empty($value)) {
                continue;
            }
            $array[$property] = $value;
        }

        return $array;
    }
}
