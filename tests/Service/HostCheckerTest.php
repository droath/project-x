<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\Service\HostChecker;
use PHPUnit\Framework\TestCase;

/**
 * Define host checker test.
 */
class HostCheckerTest extends TestCase
{
    public function setUp()
    {
        $this->hostChecker = (new HostChecker())
            ->setHost('google.com');
    }

    public function testIsPortOpenOnClosedPort()
    {
        $this->hostChecker
            ->setPort(1)
            ->setTimeout(1);

        $this->assertFalse($this->hostChecker->isPortOpen());
    }

    public function testIsPortOpenOnOpenPort()
    {
        $this->hostChecker->setPort(443);
        $this->assertTrue($this->hostChecker->isPortOpen());
    }

    public function testIsPortOpenRepeater()
    {
        $this->assertTrue($this->hostChecker->setPort(443)->isPortOpenRepeater());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing host port, ensure you called setPort().
     */
    public function testIsPortOpenOnMissingPort()
    {
        $this->hostChecker
            ->setPort(0)
            ->isPortOpen();
    }
}
