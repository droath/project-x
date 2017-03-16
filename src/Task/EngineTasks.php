<?php

namespace Droath\ProjectX\Task;

/**
 * Define Project-X engine task commands.
 */
class EngineTasks extends TaskBase
{
    /**
     * Startup project engine.
     */
    public function engineUp()
    {
        $this->engineInstance()->up();

        // Allow projects to react to the engine startup.
        $this->projectInstance()->onEngineUp();

        return $this;
    }

    /**
     * Shutdown project engine.
     */
    public function engineDown()
    {
        $this->engineInstance()->down();

        // Allow projects to react to the engine shutdown.
        $this->projectInstance()->onEngineDown();

        return $this;
    }
}
