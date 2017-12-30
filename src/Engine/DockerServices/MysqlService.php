<?php

namespace Droath\ProjectX\Engine\DockerServices;

use Droath\ProjectX\Engine\DockerService;
use Droath\ProjectX\Engine\ServiceDbInterface;
use Droath\ProjectX\Engine\ServiceInterface;

/**
 * Class MysqlService
 *
 * @package Droath\ProjectX\Engine\DockerServices
 */
class MysqlService extends DockerServiceBase implements ServiceInterface, ServiceDbInterface
{
    const DEFAULT_VERSION = 5.6;

    /**
     * {@inheritdoc}
     */
    public static function name()
    {
        return 'mysql';
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
    public function ports() {
        return ['3306'];
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        $db_type = $this->dbType();
        $database = $this->getProjectType();

        return (new DockerService())
           ->setImage($db_type, $this->getVersion())
           ->setPorts($this->getPorts())
            ->setEnvironment([
                'MYSQL_USER=admin',
                'MYSQL_PASSWORD=root',
                "MYSQL_DATABASE={$database}",
                'MYSQL_ALLOW_EMPTY_PASSWORD=1'
            ])
           ->setVolumes([
               'mysql-data:/var/lib/mysql',
               "./docker/services/{$db_type}/mysql-overrides.cnf:/etc/mysql/mysql.conf.d/99-mysql-overrides.cnf"
           ]);
    }

    /**
     * {@inheritdoc}
     */
    public function volumes()
    {
        return [
          'mysql-data' => [
              'driver' => 'local'
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function templateFiles()
    {
        return [
            'mysql-overrides.cnf' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function username()
    {
        return $this->getEnvironmentValue('MYSQL_USER');
    }

    /**
     * {@inheritdoc}
     */
    public function password()
    {
        return $this->getEnvironmentValue('MYSQL_PASSWORD');
    }

    /**
     * {@inheritdoc}
     */
    public function database()
    {
        return $this->getEnvironmentValue('MYSQL_DATABASE');
    }

    /**
     * {@inheritdoc}
     */
    public function protocol()
    {
        return 'mysql';
    }

    /**
     * The database vendor type.
     *
     * @return string
     *   The database vendor type.
     */
    protected function dbType()
    {
        return 'mysql';
    }
}
