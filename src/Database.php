<?php

namespace Droath\ProjectX;

use ReflectionProperty;

/**
 * Define a simple database object.
 */
class Database implements DatabaseInterface
{
    public $user;
    public $port;
    public $protocol;
    public $database;
    public $hostname;
    public $password;
    protected $mappings = [];

    public function __construct($mappings = [])
    {
        $this->mappings = $mappings;
    }

    public static function createFromArray(array $array, $mappings = [])
    {
        $instance = new static($mappings);

        foreach ($array as $property => $value) {
            $method = 'set' . ucwords($property);
            if (!method_exists($instance, $method)) {
                continue;
            }
            call_user_func_array([$instance, $method], [$value]);
        }

        return $instance;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return self
     */
    public function setUser($user)
    {
        if (isset($user)) {
            $this->user = $user;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return self
     */
    public function setPort($port)
    {
        if (isset($port)) {
            $this->port = $port;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string $database
     *
     * @return self
     */
    public function setDatabase($database)
    {
        if (isset($database)) {
            $this->database = $database;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     *
     * @return self
     */
    public function setHostname($hostname)
    {
        if (isset($hostname)) {
            $this->hostname = $hostname;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return self
     */
    public function setPassword($password)
    {
        if (isset($password)) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     *
     * @return self
     */
    public function setProtocol($protocol)
    {
        if (isset($protocol)) {
            $this->protocol = $protocol;
        }

        return $this;
    }

    /**
     * The array representation of database object.
     *
     * @return \ArrayIterator
     * @throws \ReflectionException
     */
    public function asArray()
    {
        $array = [];
        $properties = (new \ReflectionClass($this))
            ->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $object) {
            $value = $object->getValue($this);
            if (!isset($value)) {
                continue;
            }
            $property = $object->getName();

            if (!empty($this->mappings)
                && isset($this->mappings[$property])) {
                $property = $this->mappings[$property];
            }

            $array[$property] = $value;
        }

        return new \ArrayIterator($array);
    }
}
