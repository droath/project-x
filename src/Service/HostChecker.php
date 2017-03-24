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
     * Ping time to live.
     *
     * @var int
     */
    protected $ttl = 255;

    /**
     * Ping timeout.
     *
     * @var int
     */
    protected $timeout = 10;

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
        $this->port = (int) $port;

        return $this;
    }

    /**
     * Set ping time-to-live.
     *
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = (int) $ttl;

        return $this;
    }

    /**
     * Set ping timeout.
     *
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;

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
        return $this
            ->getPingInstance()
            ->ping('fsockopen') !== false ?: false;
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
        $start = time();
        $instance = $this->getPingInstance();

        do {
            $current = time() - $start;
            $latency = $instance->ping('fsockopen');

            if ($latency !== false) {
                return true;
            }
        } while ($current <= $seconds);

        return false;
    }

    /**
     * Get the ping instance.
     *
     * @throws \InvalidArgumentException
     *
     * @return \JJG\Ping
     *   Return the ping instance.
     */
    protected function getPingInstance()
    {
        if (empty($this->port)) {
            throw new \InvalidArgumentException(
                'Missing host port, ensure you called setPort().'
            );
        }
        $instance = $this->createPing();
        $instance
            ->setPort($this->port);

        return $instance;
    }

    /**
     * Create a ping instance.
     *
     * @throws \InvalidArgumentException
     *
     * @return \JJG\Ping
     *   Return the ping instance.
     */
    protected function createPing()
    {
        if (!isset($this->host)) {
            throw new \InvalidArgumentException(
                'Missing hostname, unable to conduct a ping request.'
            );
        }

        return new Ping($this->host, $this->ttl, $this->timeout);
    }
}
