<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\Utility;
use PHPUnit\Framework\TestCase;

class UtilityTest extends TestCase
{
    /**
     * @dataProvider stringCleanProvider
     */
    public function testCleanString($string, $expected)
    {
        $this->assertEquals($expected, Utility::cleanString($string));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCleanStringException()
    {
        Utility::cleanString(['bad-data']);
        $this->fail('This should fail because bad data was provided.');
    }

    /**
     * @dataProvider stringMachineProvider
     */
    public function testMachineName($string, $expected)
    {
        $this->assertEquals($expected, Utility::machineName($string));
    }

    public function testRandomBytes()
    {
        $this->assertTrue(
            preg_match('~[^\x20-\x7E\t\r\n]~', Utility::randomBytes(10)) > 0
        );
    }

    public function stringCleanProvider()
    {
        return [
            ['Project-X', 'Project-X'],
            ['Pr0*o()j999e <<>> ct~!@#-X', 'Project-X'],
        ];
    }

    public function stringMachineProvider()
    {
        return [
            ['Project X', 'project-x'],
            ['Pr0*o()j999e<<>>ct~!@#-X', 'project-x'],
        ];
    }
}
