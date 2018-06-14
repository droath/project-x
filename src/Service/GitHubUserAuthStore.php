<?php

namespace Droath\ProjectX\Service;

use Droath\ProjectX\Store\TokenAuthStore;

/**
 * Define GitHub token authentication store.
 */
class GitHubUserAuthStore extends TokenAuthStore
{
    /**
     * Define the store filename.
     */
    const FILE_NAME = 'github-user.yml';
}
