<?php

namespace Droath\ProjectX\Tests;

use Droath\ProjectX\ProjectX;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Define test task base class.
 */
abstract class TestTaskBase extends TestBase
{
    /**
     * Project-X application.
     *
     * @var \Droath\ProjectX\ProjectX
     */
    protected $app;

    /**
     * Robo builder object.
     *
     * @var \Robo\Collection\CollectionBuilder
     */
    protected $builder;

    /**
     * Container.
     *
     * @var \League\Container\ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->app = new ProjectX();
        $this->container = Robo::createDefaultContainer(null, new NullOutput(), $this->app);

        ProjectX::setDefaultServices($this->container);
        $this->builder = $this->container->get('collectionBuilder', [new Tasks()]);
    }
}
