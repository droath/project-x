<?php

namespace Droath\ProjectX\Engine;

/**
 * Interface ServiceDbInterface
 *
 * @package Droath\ProjectX\Engine
 */
interface ServiceDbInterface
{
    /**
     * Docker service DB protocol.
     *
     * @return string
     */
    public function protocol();
}
