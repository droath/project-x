<?php

namespace Droath\ProjectX\Task;

use Droath\ProjectX\ProjectX;
use Droath\RoboGoogleLighthouse\Task\loadTasks as loadGoogleLighthouseTasks;

/**
 * Define project-x report task commands.
 */
class ReportTasks extends TaskBase
{
    use loadGoogleLighthouseTasks;

    /**
     * Run Google lighthouse report.
     *
     * @param array $opts
     * @option string $hostname Set the hostname.
     * @option string $protocol Set the protocol to use https or http.
     */
    public function reportLighthouse($opts = [
        'hostname' => null,
        'protocol' => 'http'
    ])
    {
        $host = ProjectX::getProjectConfig()
            ->getHost();

        $protocol = $opts['protocol'];
        $hostname = isset($opts['hostname'])
            ? $opts['hostname']
            : (isset($host['name']) ? $host['name'] : 'localhost');

        $path = $this->getReportsPath() . "/lighthouse-report-$hostname.html";

        $this->taskGoogleLighthouse()
            ->setUrl("$protocol://$hostname")
            ->setOutputPath($path)
            ->run();
    }

    /**
     * Get project-x reports path.
     *
     * @return string
     *   The project-x reports path.
     */
    protected function getReportsPath()
    {
        $project_root = ProjectX::projectRoot();
        $reports_path = "$project_root/reports";

        if (!file_exists($reports_path)) {
            mkdir($reports_path);
        }

        return $reports_path;
    }
}
