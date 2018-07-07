<?php

namespace Droath\ProjectX\Tests\Project;

use Droath\ProjectX\Database;
use Droath\ProjectX\Project\PhpProjectType;
use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Tests\TestTaskBase;
use org\bovigo\vfs\vfsStream;

/**
 * Define the PHP project type test.
 *
 * @coversDefaultClass \Droath\ProjectX\Project\PhpProjectType
 */
class PhpProjectTypeTest extends TestTaskBase
{
    protected $phpProject;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->phpProject = $this->getMockForAbstractClass('\Droath\ProjectX\Project\PhpProjectType')
            ->setBuilder($this->builder)
            ->setContainer($this->container);
    }

    public function testDefaultServices()
    {
        $this->assertEquals([
            'php' => [
                'type' => 'php',
                'version' => PhpProjectType::DEFAULT_PHP7
            ]
        ], $this->phpProject->defaultServices());
    }

    public function testServiceConfigs()
    {
        // Project-x configuration is set to use a mysql database type.
        $this->assertTrue(in_array('mysql-client', $this->phpProject->serviceConfigs()['php']['PACKAGE_INSTALL']));

        // Update project-x configuration to use postgres database.
        $config = ProjectX::getProjectConfig();
        $project_options = $config->getOptions();
        $project_options['docker']['services']['database'] = [
            'type' => 'postgres',
            'version' => 9.6,
            'ports' => ['5432'],
            'environment' => [
                'POSTGRES_USER=admin',
                'POSTGRES_PASSWORD=root',
                "POSTGRES_DB=drupal",
                'PGDATA=/var/lib/postgresql/data'
            ]
        ];
        $config->setOptions($project_options);
        $config->save($this->getProjectXFilePath());

        $this->assertTrue(in_array('postgresql-client', $this->phpProject->serviceConfigs()['php']['PACKAGE_INSTALL']));
    }

    public function testGetEnvPhpVersion()
    {
        $this->assertEquals('7.1', $this->phpProject->getEnvPhpVersion());
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
        $contents = $this->getProjectFileContents('.probo.yml');
        preg_match_all('/\$SRC_DIR\/www/', $contents, $matches);
        $this->assertEquals(3, count($matches[0]));
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
        $this->assertProjectFileExists('phpunit.xml.dist');
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
        $this->assertRegExp('/\<file\>\.\/www\<\/file\>/', $this->getProjectFileContents('phpcs.xml.dist'));
        $this->assertTrue($this->phpProject->hasPhpCodeSniffer());
    }

    public function testPhpCodeSniffer()
    {
        $this->phpProject->setupPhpCodeSniffer();
        $this->assertTrue($this->phpProject->hasPhpCodeSniffer());
    }

    public function testGetPhpServiceName()
    {
        $name = $this->phpProject->getPhpServiceName();
        $this->assertEquals('php', $name);
    }

    public function testGetDatabaseInfo()
    {
        $this->assertEquals(new \ArrayIterator([
            'hostname' => 'database',
            'port' => '3307',
            'user' => 'admin',
            'password' => 'root',
            'database' => 'drupal',
            'protocol' => 'mysql',
        ]), $this->phpProject->getDatabaseInfo()->asArray());
    }

    public function testGetDatabaseInfoWithOverrides() {
        $this->phpProject->setDatabaseOverride((new Database())
            ->setPort(5253)
            ->setProtocol('pgsql')
            ->setHostname('127.0.0.1'));
        $this->assertEquals(new \ArrayIterator([
            'hostname' => '127.0.0.1',
            'port' => '5253',
            'user' => 'admin',
            'password' => 'root',
            'database' => 'drupal',
            'protocol' => 'pgsql',
        ]), $this->phpProject->getDatabaseInfo()->asArray());
    }

    public function testPackagePhpBuild()
    {
        vfsStream::create([
            'patches' => [
                'fix_me.patch' => '',
                'fix_me_2.patch' => ''
            ],
            'composer.json' => '{}',
            'composer.lock' => '{}',
        ], $this->projectDir);

        $build_root = ProjectX::buildRoot();
        $this->phpProject->packagePhpBuild($build_root);

        $this->assertFileExists("{$build_root}/composer.json");
        $this->assertFileExists("{$build_root}/composer.lock");
        $this->assertFileExists("{$build_root}/patches/fix_me.patch");
    }
}
