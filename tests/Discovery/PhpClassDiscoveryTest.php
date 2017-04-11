<?php

namespace Droath\ProjectX\Tests\Discovery;

use Droath\ProjectX\Discovery\PhpClassDiscovery;
use Droath\ProjectX\Tests\TestBase;
use org\bovigo\vfs\vfsStream;

/**
 * PHP class discovery test.
 */
class PhpClassDiscoveryTest extends TestBase
{
    /**
     * Php class discovery service.
     *
     * @var \Droath\ProjectX\Discovery\PhpClassDiscovery
     */
    protected $phpClassDiscovery;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->phpClassDiscovery = new PhpClassDiscovery();

        vfsStream::create([
            'TestingRoboTasks.php' => $this->phpFileContents('TestingRoboTasks', 'RandomInterface'),
            'NewDirectory' => [
                'AnotherClass.php' => $this->phpFileContents('AnotherClass', 'AnotherClassInterface'),
                'WhatEverClass.php' => $this->phpFileContents('WhatEverClass', 'WhatEverInterface')
            ]
        ], $this->projectDir);
    }

    /**
     * @dataProvider searchDepthDataProvider
     */
    public function testDiscoverWithSearchDepth($expected, $depth)
    {
        $files = $this->phpClassDiscovery
            ->addSearchLocation($this->projectRoot)
            ->setSearchDepth($depth)
            ->discover();

        $this->assertCount($expected, $files);
    }

    public function testDiscoverWithSearchPattern()
    {
        $files = $this->phpClassDiscovery
            ->addSearchLocation($this->projectRoot)
            ->setSearchPattern('*Tasks.php')
            ->discover();

        $this->assertCount(1, $files);
    }

    public function testDiscoverWithSearchLocation()
    {
        $files = $this->phpClassDiscovery
            ->addSearchLocation($this->projectRoot)
            ->addSearchLocation($this->projectRoot . '/NewDirectory')
            ->discover();

        $this->assertCount(3, $files);
    }

    public function testDiscoverWithSearchLocations()
    {
        $files = $this->phpClassDiscovery
            ->addSearchLocations([
                $this->projectRoot,
                $this->projectRoot . '/NewDirectory'
            ])
            ->discover();

        $this->assertCount(3, $files);
    }

    public function testDiscoverWithMatchClass()
    {
        $files = $this->phpClassDiscovery
            ->addSearchLocation($this->projectRoot . '/NewDirectory')
            ->matchClass('AnotherClass')
            ->discover();

        $this->assertCount(1, $files);
    }

    public function testDiscoverWithMatchExtend()
    {
        $files = $this->phpClassDiscovery
            ->addSearchLocation($this->projectRoot)
            ->matchExtend('NOTHING')
            ->discover();

        $this->assertEmpty($files);
    }

    public function testDiscoverWithMatchImplement()
    {
        $files = $this->phpClassDiscovery
            ->addSearchLocation($this->projectRoot)
            ->matchImplement('WhatEverInterface')
            ->setSearchDepth('<= 1')
            ->discover();

        $this->assertCount(1, $files);
    }

    public function searchDepthDataProvider()
    {
        return [
            [1, 0],
            [2, 1],
            [3, '<= 1']
        ];
    }

    protected function phpFileContents($classname, $interface = null)
    {
        $content = "<?php\n\n";
        $content .= "use Robo\Tasks;\n";
        $content .= "class $classname extends Tasks";
        $content .= isset($interface)
                    ? " implements $interface\n"
                    : "\n";
        $content .= "{\n";
        $content .= "}";

        return $content;
    }
}
