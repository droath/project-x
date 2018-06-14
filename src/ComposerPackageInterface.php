<?php

namespace Droath\ProjectX;

use Droath\ProjectX\Config\ComposerConfig;

/**
 * Composer package interface.
 */
interface ComposerPackageInterface
{
    public function alterComposer(ComposerConfig $composer);
}
