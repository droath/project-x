<?php


namespace Droath\ProjectX\Event;

interface EngineEventInterface
{
    /**
     * @return mixed
     */
    public function onEngineUp();

    /**
     * @return mixed
     */
    public function onEngineDown();
}
