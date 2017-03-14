<?php

namespace Droath\ProjectX\Service;

use JJG\Ping;

/**
 * Define the host checker class.
 */
class HostChecker
{
    /**
     * Hostname.
     *
     * @var string
     */
    protected $host;

    /**
     * Hostname port.
     *
     * @var int
     */
    protected $port = 80;

    /**
     * Set the hostname.
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Set the host port.
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Check if the host port is open.
     *
     * @return bool
     *   Return true if the host port is open; otherwise false.
     */
    public function isPortOpen()
    {
        if (!isset($this->port)) {
            throw new \Exception(
                'Missing host port, ensure you called setPort().'
            );
        }
        $instance = $this->createPing();
        $instance
            ->setPort($this->port);

        var_dump($instance->ping('fsockopen'));

        return $instance->ping('fsockopen') !== false ?: false;
    }

    /**
     * Check if the host port is opened within a timeframe.
     *
     * @param int $seconds
     *   The amount of seconds it should continuously check.
     *
     * @return bool
     *   Return true if the host port is open; otherwise false.
     */
    public function isPortOpenRepeater($seconds = 15)
    {
        if (!isset($this->port)) {
            throw new \Exception(
                'Missing host port, ensure you called setPort().'
            );
        }
        $instance = $this->createPing();
        $instance
            ->setPort($this->port);

        $start = time();
        do {
            $current = time() - $start;
            $latency = $instance->ping('fsockopen');

            if ($latency !== false) {
                return true;
            }
        } while($current <= $seconds);

        return false;
    }

    /**
     * Create a ping instance.
     *
     * @throws \Exception
     *
     * @return \JJG\Ping
     *   Return the ping instance.
     */
    protected function createPing($ttl = 255, $timeout = 10)
    {
        if (!isset($this->host)) {
            throw new \Exception(
                'Missing hostname, unable to conduct a ping request.'
            );
        }

        return new Ping($this->host, $ttl, $timeout);
    }
}
