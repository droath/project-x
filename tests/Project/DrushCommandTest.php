<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Project\DrushCommand;
use Droath\ProjectX\Tests\TestBase;

class DrushCommandTest extends TestBase
{
    protected $drushCommand;

    protected $drupalProject;

    public function setUp() {
        parent::setUp();
        $this->drushCommand = new DrushCommand();
    }

    public function testOptions()
    {
        $command = $this->drushCommand
            ->setOption('-tail')
            ->setOption('--dir', '/var/www')
            ->setOption('option', 'value');

        $this->assertEquals('-tail --dir /var/www --option value', $command->getOptions());
    }

    public function testEnableInteraction()
    {
        $command = $this->drushCommand
            ->command('cc all')
            ->enableInteraction()
            ->build();
        $this->assertEquals('drush -r vfs://root/www cc all', $command);
    }

    public function testBuildSingle()
    {
        $command = $this->drushCommand
            ->command('cc all')
            ->build();
        $this->assertEquals('drush -r vfs://root/www --yes cc all', $command);
    }

    public function testBuildMultiple()
    {
        $command = $this->drushCommand
            ->command('cex')
            ->command('updb')
            ->build();

        $this->assertEquals('drush -r vfs://root/www --yes cex && drush -r vfs://root/www --yes updb', $command);
    }
}
