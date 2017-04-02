<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\ProjectX;
use Robo\Tasks;

/**
 * Define Project-X GitHub Robo task base.
 */
abstract class GitHubTaskBase extends Tasks
{
    /**
     * Get GitHub authentication user.
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function getUser()
    {
        $info = $this
            ->gitHubUserAuth()
            ->getAuthInfo();

        if (!isset($info['user'])) {
            throw new \RuntimeException(
                "GitHub authentication user is required. \r\n\n " .
                '[info] Run vendor/bin/project-x github::auth to get started.'
            );
        }

        return $info['user'];
    }

    /**
     * Get GitHub user authentication token.
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function getToken()
    {
        $info = $this
            ->gitHubUserAuth()
            ->getAuthInfo();

        if (!isset($info['token'])) {
            throw new \RuntimeException(
                "GitHub user authentication token is required. \r\n\n " .
                '[info] Run vendor/bin/project-x github::auth to get started.'
            );
        }

        return $info['token'];
    }

    /**
     * Get project GitHub account.
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function getAccount()
    {
        $info = $this
            ->configuration()
            ->getGitHubUrlInfo();

        if (!isset($info['account'])) {
            throw new \RuntimeException(
                'GitHub URL is missing from Project-X configuration.'
            );
        }

        return $info['account'];
    }

    /**
     * Get project GitHub repository.
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function getRepository()
    {
        $info = $this
            ->configuration()
            ->getGitHubUrlInfo();

        if (!isset($info['repository'])) {
            throw new \RuntimeException(
                'GitHub URL is missing from Project-X configuration.'
            );
        }

        return $info['repository'];
    }

    /**
     * Has GitHub user authentication info.
     *
     * @return bool
     */
    protected function hasAuth()
    {
        return $this
            ->gitHubUserAuth()
            ->hasAuthInfo();
    }

    /**
     * Has Git-flow enabled.
     *
     * @return bool
     */
    protected function hasGitFlow()
    {
        $config = $this->gitConfig();

        if (isset($config['gitflow prefix'])
            && !empty($config['gitflow prefix'])) {

            return true;
        }

        return false;
    }

    /**
     * Git configuration options.
     *
     * @return array
     *   An array of the local .git/config options.
     */
    protected function gitConfig()
    {
        $config_file = ProjectX::projectRoot() . '/.git/config';

        if (!file_exists($config_file)) {
            return [];
        }

        return parse_ini_file($config_file, true);
    }

    /**
     * Normalize GitHub branch name.
     *
     * @param string $branch_name
     *   The suggested branch name.
     *
     * @return string
     *   The normalized GitHub branch name.
     */
    protected function normailizeBranchName($branch_name, $prefix = 'GH')
    {
        $branch_name = strtolower(
            preg_replace('/[^A-Za-z0-9\-]/', '', strtr(trim($branch_name), ' ', '-'))
        );

        return "$prefix-$branch_name";
    }

    /**
     * Get project configuration.
     *
     * @return \Droath\ProjectX\Config
     */
    protected function configuration()
    {
        return ProjectX::getProjectConfig();
    }

    /**
     * GitHub user authentication object.
     *
     * @return \Droath\ProjectX\Service\GitHubUserAuthStore
     */
    protected function gitHubUserAuth()
    {
        return $this
            ->getContainer()
            ->get('projectXGitHubUserAuth');
    }
}
