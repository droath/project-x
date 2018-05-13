<?php

namespace Droath\ProjectX\Config;

/**
 * Define the project-x configuration.
 */
class ProjectXConfig extends YamlConfigBase
{
    /**
     * Project name.
     *
     * @var string
     */
    protected $name;

    /**
     * Project type.
     *
     * @var string
     */
    protected $type;

    /**
     * Project type version.
     *
     * @var string
     */
    protected $version;

    /**
     * Project root.
     *
     * @var string
     */
    protected $root;

    /**
     * Project engine.
     *
     * @var string
     */
    protected $engine;

    /**
     * Project remote.
     *
     * @var array
     */
    protected $remote = [];

    /**
     * Project host information.
     *
     * @var array
     */
    protected $host = [];

    /**
     * Project GitHub information.
     *
     * @var array
     */
    protected $github = [];

    /**
     * Project options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Project command hooks.
     *
     * @var array
     */
    protected $command_hooks = [];

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setVersion($version)
    {
        $this->version = $version;

        return $version;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setEngine($engine)
    {
        $this->engine = $engine;

        return $this;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function setHost(array $values)
    {
        $this->host = $values;

        return $this;
    }

    public function setRemote(array $remote)
    {
        $this->remote = $remote;

        return $this;
    }

    public function getGithub()
    {
        return $this->github;
    }

    public function setGithub(array $values)
    {
        $this->github = $values;

        return $this;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getRemote()
    {
        return $this->remote;
    }

    public function setOptions(array $values)
    {
        $this->options = array_replace_recursive(
            $this->options,
            $values
        );

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getCommandHooks()
    {
        return $this->command_hooks;
    }

    public function setCommandHooks(array $hooks)
    {
        $this->command_hooks = $hooks;

        return $this;
    }
}
