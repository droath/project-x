<?php

namespace Droath\ProjectX\Service;

use Droath\ProjectX\DefaultYamlFileStore;

/**
 * Define GitHub user authentication store.
 */
class GitHubUserAuthStore extends DefaultYamlFileStore
{
    /**
     * Define the store filename.
     */
    const FILE_NAME = 'github-user.yml';

    /**
     * Has GitHub user authentication info.
     *
     * @return bool
     */
    public function hasAuthInfo()
    {
        return $this->hasFile();
    }

    /**
     * Get GitHub user authentication info.
     *
     * @return array
     *   An array of the GitHub user authentication details.
     */
    public function getAuthInfo()
    {
        return $this->getFileData();
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

        return $this->saveFile($results);
    }
}
