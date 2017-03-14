<?php

namespace Droath\ProjectX\Filesystem;

use Symfony\Component\Yaml\Yaml;

/**
 * Define the YAML filesystem class.
 */
class YamlFilesystem
{
    protected $basePath;

    protected $results = [];

    public function __construct(array $results, $base_path = null)
    {
        if (!isset($base_path)) {
            $base_path = getcwd();
        }

        $this->results = $results;
        $this->basePath = $base_path;
    }

    public function save($filename)
    {
        $filepath = $this->basePath . "/$filename";

        return file_put_contents($filepath, Yaml::dump($this->results, 4, 2));
    }
}
