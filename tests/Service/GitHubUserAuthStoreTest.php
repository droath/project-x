<?php

namespace Droath\ProjectX\Tests\Service;

use Droath\ProjectX\Service\GitHubUserAuthStore;
use Droath\ProjectX\Tests\TestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Define GitHub user authentication store test.
 */
class GitHubUserAuthStoreTest extends TestBase
{
    public function setUp()
    {
        parent::setUp();

        $this->filename = GitHubUserAuthStore::FILE_NAME;
        $this->filepath = $this->getProjectFileUrl(
            $this->getGithubUserAuthFilePath()
        );
        $this->githubUserAuth = new GitHubUserAuthStore($this->filepath);
    }

    public function testHasAuthInfo()
    {
        $this->assertFalse($this->githubUserAuth->hasAuthInfo());
        $this->addGithubUserAuthFile();
        $this->assertTRUE($this->githubUserAuth->hasAuthInfo());
    }

    public function testGetAuthInfoNoContent()
    {
        $info = $this->githubUserAuth->getAuthInfo();
        $this->assertEmpty($info);
        $this->assertInternalType('array', $info);

    }

    public function testGetAuthInfoWithContent()
    {
        $this->addGithubUserAuthFile();

        $info = $this->githubUserAuth->getAuthInfo();
        $this->assertEquals('test-user', $info['user']);
        $this->assertEquals('3253523452352', $info['token']);
    }

    public function testSaveAuthInfo()
    {
        $user = 'test-user';
        $token = '3253523452352';

        $auth = $this->githubUserAuth
            ->saveAuthInfo($user, $token);

        $fileurl = "{$this->filepath}/{$this->filename}";
        $this->assertFileExists($fileurl);

        $contents = Yaml::parse(
            file_get_contents($fileurl)
        );

        $this->assertNotFalse($auth);
        $this->assertEquals($user, $contents['user']);
        $this->assertEquals($token, $contents['token']);
    }

    protected function getGithubUserAuthFilePath()
    {
        return implode(DIRECTORY_SEPARATOR, [
            GitHubUserAuthStore::FILE_DIR,
        ]);
    }
}
