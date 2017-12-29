<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\ServiceDbInterface;
use Droath\ProjectX\Engine\ServiceInterface;

/**
 * Class PostgresService
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
class PostgresService extends DockerServiceBase implements ServiceInterface, ServiceDbInterface
{
    const DEFAULT_VERSION = 9.6;

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'postgres';
    }

    /**
     * {@inheritdoc}
     */
    public static function group()
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    public function ports()
    {
        return ['5432'];
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        $database = $this->getProjectType();
        return (new DockerService())
            ->setImage('postgres', $this->getVersion())
            ->setPorts($this->getPorts())
            ->setEnvironment([
                'POSTGRES_USER=admin',
                'POSTGRES_PASSWORD=root',
                "POSTGRES_DB={$database}",
                'PGDATA=/var/lib/postgresql/data'
            ])
            ->setVolumes([
               'pgsql-data:/var/lib/postgresql/data'
           ]);
    }

    /**
     * {@inheritdoc}
     */
    public function volumes()
    {
        return [
          'pgsql-data' => [
              'driver' => 'local'
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function protocol()
    {
        return 'pgsql';
    }
}
