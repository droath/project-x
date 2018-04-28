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
     * SSH in the environment engine.
     */
    public function ssh();

    /**
     * Shutdown the engine infrastructure.
     */
    public function down();

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
     */
    public function reboot();

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
}
