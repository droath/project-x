<?php

namespace Droath\ProjectX\Store;

/**
 * Generic token authentication store.
 */
abstract class TokenAuthStore extends DefaultYamlFileStore
{
    /**
     * Set the token user.
     *
     * @param $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->contents['user'] = $user;

        return $this;
    }

    /**
     * Set the token value.
     *
     * @param $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->contents['token'] = $token;

        return $this;
    }
}
