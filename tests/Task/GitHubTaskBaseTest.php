<?php

namespace Droath\ProjectX\Tests\Task;

use Droath\ProjectX\Service\GitHubUserAuthStore;
use Droath\ProjectX\Tests\TestTaskBase;

class GitHubTaskBaseTest extends TestTaskBase
{
    /**
     * GitHub task base mock.
     *
     * @var \Droath\ProjectX\Task\GitHubTaskBase
     */
    protected $gitHubTaskBase;

    /**
     * GitHub task directory path.
     *
     * @var string
     */
    protected $gitHubTaskPath;

    public function setUp()
    {
        parent::setUp();

        $this->gitHubTaskBase = $this
            ->getMockForAbstractClass('\Droath\ProjectX\Task\GitHubTaskBase');

        $this->gitHubTaskPath = $this
            ->getProjectFileUrl(GitHubUserAuthStore::FILE_DIR);

        $this->container
            ->share('projectXGitHubUserAuth', '\Droath\ProjectX\Service\GitHubUserAuthStore')
            ->withArgument($this->gitHubTaskPath);

        $this->gitHubTaskBase->setContainer($this->container);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetUserNoContent()
    {
        $this->gitHubTaskBase->getUser();
    }

    public function testGetUserWithContent()
    {
        $this->addGithubUserAuthFile();
        $this->assertEquals('test-user', $this->gitHubTaskBase->getUser());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetTokenNoContent()
    {
        $this->gitHubTaskBase->getToken();
    }

    public function testGetTokenWithContent()
    {
        $this->addGithubUserAuthFile();
        $this->assertEquals('3253523452352', $this->gitHubTaskBase->getToken());
    }

    public function testGetAccount()
    {
        $this->assertEquals('droath', $this->gitHubTaskBase->getAccount());
    }

    public function testgetRepository()
    {
        $this->assertEquals('project-x', $this->gitHubTaskBase->getRepository());
    }
}
