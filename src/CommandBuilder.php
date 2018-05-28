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
     * Command environment variable.
     *
     * @var string
     */
    protected $envVariable = [];

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
     * @param null $delimiter
     *   The command option delimiter.
     *
     * @return $this
     */
    public function setOption($option, $value = null, $delimiter = null)
    {
        $delimiter = isset($delimiter) ? $delimiter : " ";

        $this->options[] = strpos($option, '-') !== false
            ? "{$option}{$delimiter}{$value}"
            : "--{$option}{$delimiter}{$value}";

        return $this;
    }

    /**
     * Get environment variable.
     *
     * @return string
     */
    public function getEnvVariable()
    {
        $variables = array_map('trim', $this->envVariable);
        return implode(' ;', $variables);
    }

    /**
     * Set environment variable.
     *
     * @param $key
     *   The environment variable key.
     * @param $value
     *   The environment variable value.
     *
     * @return $this
     */
    public function setEnvVariable($key, $value)
    {
        $this->envVariable[] = "{$key}={$value}";

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
            $commands[] = trim("{$this->getEnvVariable()} {$this->executable} {$this->getOptions()} {$command}");
        }
        $this->commands = [];

        return implode(' && ', $commands);
    }
}
