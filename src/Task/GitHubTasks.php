<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\ProjectX;
use Droath\ProjectX\Utility;
use Droath\RoboGitHub\Task\loadTasks as loadGitHubTasks;
use Droath\RoboGoogleLighthouse\Task\loadTasks as loadLighthouseTasks;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Define Project-X GitHub Robo tasks.
 */
class GitHubTasks extends GitHubTaskBase
{
    use loadGitHubTasks;
    use loadLighthouseTasks;

    /**
     * Authenticate GitHub user.
     */
    public function githubAuth()
    {
        if ($this->hasAuth()) {
            $this->io()->warning(
                'A personal GitHub access token has already been setup.'
            );

            return;
        }
        $this->io()->note("A personal GitHub access token is required.\n\n" .
            'If you need help setting up a access token follow the GitHub guide:' .
            ' https://help.github.com/articles/creating-a-personal-access-token-for-the-command-line/');

        $user = $this->ask('GitHub username:');
        $pass = $this->askHidden('GitHub token (hidden):');

        $status = $this
            ->gitHubUserAuth()
            ->saveAuthInfo($user, $pass);

        if (!$status) {
            $this->io()->success(
                "You've successfully added your personal GitHub access token."
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

        $this
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
     * GitHub lighthouse status check.
     *
     * @param string $sha
     * @param array $opts
     * @option string $url Set the URL hostname.
     * @option string $protocol Set the URL protocol http or https.
     * @option bool $performance Validate performance score.
     * @option bool $performance-score Set the performance score requirement.
     * @option bool $accessibility Validate accessibility score.
     * @option bool $accessibility-score Set the accessibility score requirement.
     * @option bool $best-practices Validate best-practices score.
     * @option bool $best-practices-score Set the best-practices score requirement.
     * @option bool $progressive-web-app Validate progressive-web-app score.
     * @option bool $progressive-web-app-score Set the progressive-web-app score requirement.
     */
    public function githubLighthouseStatus($sha, $opts = [
        'hostname' => null,
        'protocol' => 'http',
        'performance' => false,
        'performance-score' => 50,
        'accessibility' => false,
        'accessibility-score' => 50,
        'best-practices' => false,
        'best-practices-score' => 50,
        'progressive-web-app' => false,
        'progressive-web-app-score' => 50,
    ])
    {
        $host = ProjectX::getProjectConfig()
            ->getHost();

        $protocol = $opts['protocol'];
        $hostname = isset($opts['hostname'])
            ? $opts['hostname']
            : (isset($host['name']) ? $host['name'] : 'localhost');

        $url = "$protocol://$hostname";
        $path = new \SplFileInfo("/tmp/projectx-lighthouse-$sha.json");

        $this->taskGoogleLighthouse()
            ->setUrl($url)
            ->setOutput('json')
            ->setOutputPath($path)
            ->run();

        if (!file_exists($path)) {
            throw new \RuntimeException(
                'Unable to locate the Google lighthouse results.'
            );
        }
        $selected = array_filter([
            'performance',
            'accessibility',
            'best-practices',
            'progressive-web-app'
        ], function ($key) use ($opts) {
            return $opts[$key] === true;
        });

        $report_data = $this->findLighthouseScoreReportData(
            $path,
            $selected
        );
        $state = $this->determineLighthouseState($report_data, $opts);

        $this->taskGitHubRepoStatusesCreate($this->getToken())
            ->setAccount($this->getAccount())
            ->setRepository($this->getRepository())
            ->setSha($sha)
            ->setParamState($state)
            ->setParamDescription('Google Lighthouse Tests')
            ->setParamContext('project-x/lighthouse')
            ->run();
    }

    /**
     * Determine Google lighthouse state.
     *
     * @param array $values
     *   An array of formatted values.
     * @param array $opts
     *   An array of command options.
     *
     * @return string
     *   The google lighthouse state based on the evaluation.
     */
    protected function determineLighthouseState(array $values, array $opts)
    {
        $state = 'success';

        foreach ($values as $key => $info) {
            $required_score = isset($opts["$key-score"])
                ? ($opts["$key-score"] <= 100 ? $opts["$key-score"] : 100)
                : 50;

            if ($info['score'] < $required_score
                && $info['score'] !== $required_score) {
                return 'failure';
            }
        }

        return $state;
    }

    /**
     * Find Google lighthouse score report data.
     *
     * @param \SplFileInfo $path
     *   The path the Google lighthouse report.
     * @param array $selected
     *   An array of sections to retrieve scores for.
     *
     * @return array
     *   An array of section data with name, and scores.
     */
    protected function findLighthouseScoreReportData(\SplFileInfo $path, array $selected)
    {
        $data = [];
        $json = json_decode(file_get_contents($path), true);

        foreach ($json['reportCategories'] as $report) {
            if (!isset($report['name']) || !isset($report['score'])) {
                continue;
            }
            $label = $report['name'];
            $key = Utility::cleanString(
                strtolower(strtr($label, ' ', '_')),
                '/[^a-zA-Z\_]/'
            );

            if (!in_array($key, $selected)) {
                continue;
            }

            $data[$key] = [
                'name' => $label,
                'score' => round($report['score'])
            ];
        }

        return $data;
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
