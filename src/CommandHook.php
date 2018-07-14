<?php

namespace Droath\ProjectX;

/**
 * Define command hook class.
 */
class CommandHook implements CommandHookInterface
{
    protected $type;

    protected $command;

    protected $options = [];

    protected $arguments = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucwords($key);
            if (!method_exists($this, $method)) {
                continue;
            }
            call_user_func_array([$this, $method], [$value]);
        }
    }

    public static function createWithData(array $data)
    {
        return new static($data);
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    public function hasOptions()
    {
        return isset($this->options) && !empty($this->options);
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function hasArguments()
    {
        return isset($this->arguments) && !empty($this->arguments);
    }
}
