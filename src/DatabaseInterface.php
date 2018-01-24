<?php

namespace Droath\ProjectX;

interface DatabaseInterface
{

    /**
     * Database username.
     *
     * @return string
     */
    public function getUser();

    /**
     * Database port.
     *
     * @return int
     */
    public function getPort();

    /**
     * Database protocol.
     *
     * @return string
     */
    public function getProtocol();

    /**
     * Database password.
     *
     * @return string
     */
    public function getPassword();

    /**
     * Database name.
     *
     * @return string
     */
    public function getDatabase();

    /**
     * Database hostname.
     *
     * @return string
     */
    public function getHostname();
}
