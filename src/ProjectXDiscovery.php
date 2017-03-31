<?php

namespace Droath\ProjectX;

/**
 * Define Project-X discovery service.
 */
class ProjectXDiscovery
{
    /**
     * Project-X configuration filename.
     */
    const CONFIG_FILENAME = 'project-x';

    /**
     * Project-X path.
     *
     * @var string
     */
    protected $projectXPath = null;

    /**
     * Execute the Project-X discovery process.
     *
     * @return string
     */
    public function execute()
    {
        if (!isset($this->projectXPath)) {
            $this->performSearch();
        }

        return $this->projectXPath;
    }

    /**
     * Perform search for the Project-X configuration.
     *
     * @return string
     */
    protected function performSearch()
    {
        $filename = self::CONFIG_FILENAME . '.yml';
        $directories = array_filter(explode('/', getcwd()));

        $count = count($directories);
        for ($offset = 0; $offset < $count; ++$offset) {
            $next_path = '/' . implode('/', array_slice($directories, 0, $count - $offset));

            if (file_exists("{$next_path}/{$filename}")) {
                $this->projectXPath = "{$next_path}/{$filename}";
                break;
            }
        }
    }
}
