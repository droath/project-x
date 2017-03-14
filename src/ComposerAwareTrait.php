<?php

namespace Droath\ProjectX;

// Define the composer aware trait.
trait ComposerAwareTrait
{
    /**
     * Get composer JSON decoded output.
     *
     * @return array
     */
    protected function getComposerConfig()
    {
        $contents = $this->getComposerRawConfig();

        if (!$contents) {
            return [];
        }

        return json_decode($contents, true);
    }

    /**
     * Get composer raw output.
     *
     * @return string
     */
    protected function getComposerRawConfig()
    {
        $path = $this->findComposerConfigPath();

        if (!$path) {
            throw new \Exception(
                "Can't find composer.json inside the project."
            );
        }

        return file_get_contents($path);
    }

    /**
     * Find the composer file path.
     *
     * @return string
     */
    protected function findComposerConfigPath()
    {
        $path = getcwd();
        $filename = 'composer.json';

        if (!file_exists("{$path}/{$filename}")) {
            return false;
        }

        return "{$path}/{$filename}";
    }
}
