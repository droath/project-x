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
        $service =  (new DockerService())
            ->setImage('postgres', $this->getVersion())
            ->setEnvironment([
                'POSTGRES_USER=admin',
                'POSTGRES_PASSWORD=root',
                "POSTGRES_DB={$database}",
                'PGDATA=/var/lib/postgresql/data'
            ])
            ->setVolumes([
               'pgsql-data:/var/lib/postgresql/data'
            ]);

        return $this->alterService($service);
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
    public function username()
    {
        return $this->getEnvironmentValue('POSTGRES_USER');
    }

    /**
     * {@inheritdoc}
     */
    public function password()
    {
        return $this->getEnvironmentValue('POSTGRES_PASSWORD');
    }

    /**
     * {@inheritdoc}
     */
    public function database()
    {
        return $this->getEnvironmentValue('POSTGRES_DB');
    }

    /**
     * {@inheritdoc}
     */
    public function protocol()
    {
        return 'pgsql';
    }
}
