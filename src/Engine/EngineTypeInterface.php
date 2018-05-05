<?php

namespace Droath\ProjectX\Engine;

/**
 * Define engine type interface.
 */
interface EngineTypeInterface
{
    /**
     * Startup the engine infrastructure.
     */
    public function up();

    /**
     * Shutdown the engine infrastructure.
     *
     * @param bool $include_network Reboot the network.
     */
    public function down($include_network);

    /**
     * Start the engine infrastructure.
     */
    public function start();

    /**
     * Restart the engine infrastructure.
     */
    public function restart();

    /**
     * Reboot the engine infrastructure.
     *
     * @param bool $include_network Reboot the network.
     */
    public function reboot($include_network);

    /**
     * Suspend the engine infrastructure.
     */
    public function suspend();

    /**
     * Install generic engine configurations.
     */
    public function install();

    /**
     * Rebuild generic engine configurations.
     */
    public function rebuild();

    /**
     * SSH into the environment engine.
     *
     * @param null $service
     *   The specific service name.
     */
    public function ssh($service = null);

    /**
     * Display logs for the environment engine.
     *
     * @param string $show
     *   The amount of lines in the log to output.
     * @param bool $follow
     *   Determine if the log should be followed.
     * @param null $service
     *   The specific service name.
     */
    public function logs($show = 'all', $follow = false, $service = null);

    /**
     * Execute an arbitrary command in the environment engine.
     *
     * @param $command
     *   The command to execute.
     * @param null $service
     *   The specific service name.
     *
     * @return bool|string
     */
    public function exec($command, $service = null);
}
