<?php

namespace Droath\ProjectX\Task;

use Droath\RoboGitHub\Task\loadTasks as loadGitHubTasks;
use Robo\Common\IO;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Define Project-X GitHub Robo tasks.
 */
class GitHubTasks extends GitHubTaskBase
{
    use IO;
    use loadGitHubTasks;

    /**
     * Authenticate GitHub user.
     */
    public function githubAuth()
    {
        if (!$this->hasAuth()) {
            $this->say(
                'GitHub authentication token has already been added.'
            );

            return;
        }
        $user = $this->ask('GitHub username:');
        $pass = $this->askHidden('GitHub token:');

        $status = $gitHubAuth
            ->saveAuthInfo($user, $pass);

        if (!$status) {
            $this->say(
                "You've successfully added your GitHub authentication token."
            );
        }
    }

    /**
     * List GitHub issues.
     */
    public function githubIssues()
    {
        $this->outputGitHubIssues();
    }

    /**
     * Start working a GitHub issue.
     */
    public function githubIssueStart()
    {
        $listings = $this
            ->getIssueListing();

        $issue = $this->doAsk(
            new ChoiceQuestion('Select GitHub issue:', $listings)
        );
        $user = $this->getUser();
        $number = array_search($issue, $listings);

        $response = $this
            ->taskGitHubIssueAssignees($this->getToken())
            ->setAccount($this->getAccount())
            ->setRepository($this->getRepository())
            ->number($number)
            ->addAssignee($user)
            ->run();

        $this->say(
            sprintf('GH-%d issue was assigned to %s on GitHub.', $number, $user)
        );

        if ($this->ask('Create Git branch? (yes/no) [no] ')) {
            $branch = $this->normailizeBranchName("$number-$issue");
            $command = $this->hasGitFlow()
                ? "flow feature start '$branch'"
                : "checkout -b '$branch'";

            $this->taskGitStack()
                ->stopOnFail()
                ->exec($command)
                ->run();
        }
    }

    /**
     * Output GitHub issue table.
     *
     * @return self
     */
    protected function outputGitHubIssues()
    {
        $issues = $this
            ->taskGitHubIssueList(
                $this->getToken(),
                $this->getAccount(),
                $this->getRepository()
            )
            ->run();

        unset($issues['time']);

        $table = (new Table($this->output))
            ->setHeaders(['Issue', 'Title', 'State', 'Assignee', 'Labels', 'Author']);

        $rows = [];
        foreach ($issues as $issue) {
            $labels = isset($issue['labels'])
                ? $this->formatLabelNames($issue['labels'])
                : null;

            $assignee = isset($issue['assignee']['login'])
                ? $issue['assignee']['login']
                : 'none';

            $rows[] = [
                $issue['number'],
                $issue['title'],
                $issue['state'],
                $assignee,
                $labels,
                $issue['user']['login'],
            ];
        }
        $table->setRows($rows);
        $table->render();

        return $this;
    }

    /**
     * Get GitHub issue listing.
     *
     * @return array
     */
    protected function getIssueListing()
    {
        $issues = $this
            ->taskGitHubIssueList(
                $this->getToken(),
                $this->getAccount(),
                $this->getRepository()
            )
            ->run();

        $listing = [];
        foreach ($issues as $issue) {
            if (!isset($issue['title'])) {
                continue;
            }
            $number = $issue['number'];
            $listing[$number] = $issue['title'];
        }

        return $listing;
    }

    /**
     * Format GitHub labels.
     *
     * @param array $labels
     *   An array of labels returned from API resource.
     *
     * @return string
     *   A comma separated list of GitHub labels.
     */
    protected function formatLabelNames(array $labels)
    {
        if (empty($labels)) {
            return;
        }
        $names = [];

        foreach ($labels as $label) {
            if (!isset($label['name'])) {
                continue;
            }
            $names[] = $label['name'];
        }

        return implode(', ', $names);
    }
}
