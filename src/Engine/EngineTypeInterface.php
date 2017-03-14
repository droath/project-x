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
     */
    public function down();

    /**
     * Install generic engine configurations.
     */
    public function install();
}
