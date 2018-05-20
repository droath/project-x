<?php

namespace Droath\ProjectX;

/**
 * Define command builder base class.
 */
abstract class CommandBuilder
{
    /**
     * Command options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Command executable.
     *
     * @var string
     */
    protected $executable;

    /**
     * Executable commands.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Set command executable.
     *
     * @param $executable
     *   The command executable binary.
     *
     * @return $this
     */
    public function setExecutable($executable)
    {
        $this->executable = $executable;

        return $this;
    }

    /**
     * Add an executable command.
     *
     * @param $command
     *   The command for the given executable.
     *
     * @return $this
     */
    public function command($command)
    {
        $this->commands[] = $command;

        return $this;
    }

    /**
     * Get command options.
     *
     * @return string
     */
    public function getOptions()
    {
        $options = array_map('trim', $this->options);
        return implode(' ', array_unique($options));
    }

    /**
     * Set command option.
     *
     * @param $option
     *   The command option key.
     * @param null|string $value
     *   The command option value.
     *
     * @return $this
     */
    public function setOption($option, $value = null)
    {
        $this->options[] = strpos($option, '-') !== false
            ? "{$option} {$value}"
            : "--{$option} {$value}";

        return $this;
    }

    /**
     * Build the command structure.
     *
     * @return string
     */
    public function build()
    {
        $commands = [];

        foreach ($this->commands as $command) {
            $commands[] = "{$this->executable} {$this->getOptions()} {$command}";
        }
        $this->commands = [];

        return implode(' && ', $commands);
    }
}
