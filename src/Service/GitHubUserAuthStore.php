<?php

namespace Droath\ProjectX\Service;

use Droath\ProjectX\Filesystem\YamlFilesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Define GitHub user authentication store.
 */
class GitHubUserAuthStore
{
    const FILE_DIR = '.project-x';
    const FILE_NAME = 'github-user.yml';

    /**
     * File path.
     *
     * @var string
     */
    protected $filepath;

    /**
     * Constructor for GitHub user authentication.
     *
     * @param string $filepath
     *   The file path to the directory where the file is stored.
     */
    public function __construct($filepath = null)
    {
        if (!isset($filepath)) {
            $filepath = $this->findAuthFilePath();
        }

        $this->filepath = $filepath;
    }

    /**
     * Has GitHub user authentication info.
     *
     * @return bool
     */
    public function hasAuthInfo()
    {
        $filename = self::FILE_NAME;

        return file_exists("{$this->filepath}/{$filename}");
    }

    /**
     * Get GitHub user authentication info.
     *
     * @return array
     *   An array of the GitHub user authentication details.
     */
    public function getAuthInfo()
    {
        if (!$this->hasAuthInfo()) {
            return [];
        }
        $filename = self::FILE_NAME;
        $contents = file_get_contents("{$this->filepath}/{$filename}");

        return Yaml::parse($contents);
    }

    /**
     * Save GitHub user authentication info.
     *
     * @param string $user
     *   The GitHub username.
     * @param string $token
     *   The GitHub token.
     *
     * @return int|bool
     *   The number of bytes that were written to the file, or FALSE.
     */
    public function saveAuthInfo($user, $token)
    {
        $results = [
            'user' => $user,
            'token' => $token,
        ];

        // If the file path doesn't exist then create the structure.
        if (!file_exists($this->filepath)) {
            mkdir($this->filepath);
        }

        return (new YamlFilesystem($results, $this->filepath))
            ->save(self::FILE_NAME);
    }

    /**
     * Default GitHub user authentication file.
     *
     * @return string
     */
    protected function defaultFilePath()
    {
        return implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'],
            self::FILE_DIR,
        ]);
    }

    /**
     * Default GitHub user authentication file locations.
     *
     * @return array
     */
    protected function defaultLocations()
    {
        $curr_dir = getcwd();
        $file_dir = self::FILE_DIR;

        return [
            "{$_SERVER['HOME']}/{$file_dir}",
            "{$curr_dir}/{$file_dir}",
        ];
    }

    /**
     * Find GitHub user authentication file path.
     *
     * @return string
     */
    protected function findAuthFilePath()
    {
        $filename = self::FILE_NAME;

        foreach ($this->defaultLocations() as $location) {
            if (file_exists("{$location}/$filename")) {
                return $location;
            }
        }

        return $this->defaultFilePath();
    }
}
