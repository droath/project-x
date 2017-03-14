<?php

namespace Droath\ProjectX;

use Symfony\Component\Yaml\Yaml;

/**
 * Define the Project-X aware trait.
 */
trait ProjectXAwareTrait
{
    /**
     * Project-X config path.
     *
     * @var string|bool
     */
    protected $projectXConfigPath = false;

    /**
     * Get Project-X parse output.
     *
     * @return array
     */
    protected function getProjectXConfig()
    {
        $contents = $this->getProjectXRawConfig();

        if (!$contents) {
            return [];
        }

        return Yaml::parse($contents);
    }

    /**
     * Get Project-X raw output.
     *
     * @return string
     */
    protected function getProjectXRawConfig()
    {
        $path = $this->findProjectXConfigPath();

        if (!$path) {
            throw new \Exception(
                'Missing project-x.yml file.'
            );
        }

        return file_get_contents($path);
    }

    /**
     * Get the Project-X root path.
     *
     * @return string|null
     */
    protected function getProjectXRootPath()
    {
        $path = $this->findProjectXConfigPath();

        return !$path ? null : dirname($path);
    }

    /**
     * Find Project-x file path.
     *
     * @param string $path
     *   The starting directory path.
     *
     * @return string|bool
     *   The project-x path; otherwise false if not found.
     */
    protected function findProjectXConfigPath($path = null)
    {
        if (!$this->projectXConfigPath) {
            if (!isset($path)) {
                $path = getcwd();
            }

            $filename = 'project-x.yml';
            $directories = array_filter(explode('/', $path));

            $count = count($directories);
            for ($offset = 0; $offset < $count; ++$offset) {
                $next_path = '/' . implode('/', array_slice($directories, 0, $count - $offset));

                if (file_exists("$next_path/$filename")) {
                    $this->projectXConfigPath = "$next_path/$filename";
                    break;
                }
            }
        }

        return $this->projectXConfigPath;
    }
}
