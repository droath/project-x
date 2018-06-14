<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Project\Command\DrushCommand;
use Droath\ProjectX\Tests\TestBase;

class DrushCommandTest extends TestBase
{
    protected $drushCommand;

    protected $drupalProject;

    public function setUp() {
        parent::setUp();
        $this->drushCommand = new DrushCommand();
    }

    public function testEnableInteraction()
    {
        $command = $this->drushCommand
            ->command('cc all')
            ->enableInteraction()
            ->build();
        $this->assertEquals('drush -r /var/www/html/www cc all', $command);
    }

    public function testBuildSingle()
    {
        $command = $this->drushCommand
            ->command('cc all')
            ->build();
        $this->assertEquals('drush -r /var/www/html/www --yes cc all', $command);
    }

    public function testBuildMultiple()
    {
        $command = $this->drushCommand
            ->command('cex')
            ->command('updb')
            ->build();

        $this->assertEquals('drush -r /var/www/html/www --yes cex && drush -r /var/www/html/www --yes updb', $command);
    }
}
