<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Project\PhpProjectType;
use Droath\ProjectX\Tests\TestTaskBase;
use Symfony\Component\Console\Input\StringInput;

/**
 * Define the PHP project type test.
 *
 * @coversDefaultClass \Droath\ProjectX\Project\PhpProjectType
 */
class PhpProjectTypeTest extends TestTaskBase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->phpProject = (new PhpProjectType())
            ->setBuilder($this->builder)
            ->setContainer($this->container);
    }

    public function testSetupTravisCi()
    {
        $this->phpProject->setupTravisCi();
        $this->assertProjectFileExists('.travis.yml');
    }

    public function testSetupProboCi()
    {
        $this->phpProject->setupProboCi();
        $this->assertProjectFileExists('.probo.yml');
    }

    /**
     * @covers ::setupBehat
     * @covers ::hasBehat
     */
    public function testSetupBehat()
    {
        $this->phpProject->setupBehat();

        $this->assertProjectFileExists('tests/Behat/behat.yml');
        $this->assertFilePermission('0775', $this->getProjectFileUrl('tests/Behat'));

        $this->assertTrue($this->phpProject->hasBehat());
    }

    /**
     * @covers ::setupPhpUnit
     * @covers ::hasPhpUnit
     */
    public function testSetupPhpUnit()
    {
        $this->phpProject->setupPhpUnit();

        $this->assertProjectFileExists('tests/bootstrap.php');
        $this->assertFilePermission('0775', $this->getProjectFileUrl('tests/PHPunit'));

        $this->assertTrue($this->phpProject->hasPhpUnit());
    }

    /**
     * @covers ::setupPhpCodeSniffer
     * @covers ::hasPhpCodeSniffer
     */
    public function testSetupPhpCodeSniffer()
    {
        $this->phpProject->setupPhpCodeSniffer();

        $this->assertProjectFileExists('phpcs.xml.dist');
        $this->assertTrue($this->phpProject->hasPhpCodeSniffer());
    }
}
