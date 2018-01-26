<?php namespace Langdi\Scrapyd;

use GuzzleHttp\Client;

class ScrapydAPI {
    const DAEMON_STATUS_ENDPOINT = "daemonstatus.json";
    const SCHEDULE_ENDPOINT = "schedule.json";
    const ADD_VERSION_ENDPOINT = "addversion.json";
    const CANCEL_ENDPOINT = "cancel.json";
    const LIST_PROJECTS_ENDPOINT = "listprojects.json";
    const LIST_VERSIONS_ENDPOINT = "listversions.json";
    const LIST_SPIDERS_ENDPOINT = "listspiders.json";
    const LIST_JOBS_ENDPOINT = "listjobs.json";
    const DELETE_VERSION_ENDPOINT = "delversion.json";
    const DELETE_PROJECT_ENDPOINT = "delproject.json";

    private $url;
    /**
     * @var $client Client
     */
    private $client;
    private $endpoints = [
        self::DAEMON_STATUS_ENDPOINT,
        self::SCHEDULE_ENDPOINT,
        self::ADD_VERSION_ENDPOINT,
        self::CANCEL_ENDPOINT,
        self::LIST_PROJECTS_ENDPOINT,
        self::LIST_VERSIONS_ENDPOINT,
        self::LIST_SPIDERS_ENDPOINT,
        self::LIST_JOBS_ENDPOINT,
        self::DELETE_VERSION_ENDPOINT,
        self::DELETE_PROJECT_ENDPOINT
    ];

    public function __construct($url, $client = null) {
        // Add trailing slash to URL
        if (mb_substr($url, mb_strlen($url) - 1, 1) !== '/') {
            $url .= '/';
        }
        $this->url = $url;

        if ($client == null) {
            $this->client = new Client();
        } else {
            $this->client = $client;
        }
    }

    private function buildUrl($endpoint) {
        if (!in_array($endpoint, $this->endpoints)) {
            throw new \Exception("Chosen endpoint '$endpoint' is not registered as a valid endpoint");
        }

        return $this->url . $endpoint;

    }

    private function buildLogUrl($project, $spider, $jobid) {
        return $this->url."logs/{$project}/{$spider}/{$jobid}.log";
    }

    public function daemonStatus() {
        $url = $this->buildUrl(self::DAEMON_STATUS_ENDPOINT);
        $response = $this->client->get($url);
        $json = $response->getBody();

        return \GuzzleHttp\json_decode($json);
    }

    private static function stringifyBooleans(array $arr) {
        foreach ($arr as $key => $value) {
            if (is_array($arr[$key])) {
                $arr[$key] = self::stringifyBooleans($arr[$key]);
            } else {
                if ($arr[$key] === true) {
                    $arr[$key] = "True";
                }
                if ($arr[$key] === false) {
                    $arr[$key] = "False";
                }
            }
        }

        return $arr;
    }

    public function schedule($project, $spider, $settings = [], $arguments = [], $jobid = "", $version = "") {
        $url = $this->buildUrl(self::SCHEDULE_ENDPOINT);
        $data = compact('project', 'spider', 'version');
        $data = array_merge($data, $arguments);
        if ($jobid !== "") {
            $data['jobid'] = $jobid;
        }

        $data = self::stringifyBooleans($data);
        $settings = self::stringifyBooleans($settings);

        $dataString = http_build_query($data);

        if (!empty($settings)) {
            foreach ($settings as $setting => $value) {
                $dataString .= "&setting=$setting=$value";
            }
        }

        $response = $this->client->post($url, [
            'body' => $dataString,
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']
        ]);

        return \GuzzleHttp\json_decode($response->getBody());
    }

    public function cancel($project, $job) {
        $url = $this->buildUrl(self::CANCEL_ENDPOINT);

        $data = compact('project', 'job');
        $response = $this->client->post($url, ['form_params' => $data]);

        return \GuzzleHttp\json_decode($response->getBody());
    }

    public function listProjects() {
        $url = $this->buildUrl(self::LIST_PROJECTS_ENDPOINT);
        $response = $this->client->get($url);

        return \GuzzleHttp\json_decode($response->getBody());
    }

    public function listVersions($project) {
        $url = $this->buildUrl(self::LIST_VERSIONS_ENDPOINT);
        $data = compact('project');
        $queryString = http_build_query($data);
        $response = $this->client->get($url . '?' . $queryString);

        return \GuzzleHttp\json_decode($response->getBody());
    }

    public function listJobs($project) {
        $url = $this->buildUrl(self::LIST_JOBS_ENDPOINT);
        $data = compact('project');
        $queryString = http_build_query($data);
        $response = $this->client->get($url . '?' . $queryString);

        return \GuzzleHttp\json_decode($response->getBody());
    }

    public function addVersion($project, $version, $egg) {
        // TODO: Implement this (needs eggifying)
    }

    public function listSpiders($project, $version = "") {
        $url = $this->buildUrl(self::LIST_SPIDERS_ENDPOINT);

        $data = compact('project', 'version');
        $queryString = http_build_query($data);
        $response = $this->client->get($url . '?' . $queryString);

        return \GuzzleHttp\json_decode($response->getBody());
    }

    public function deleteVersion($project, $version) {
        $url = $this->buildUrl(self::DELETE_VERSION_ENDPOINT);
        $data = compact('project', 'version');
        $response = $this->client->post($url, ['form_params' => $data]);

        return \GuzzleHttp\json_decode($response->getBody());
    }

    public function deleteProject($project) {
        $url = $this->buildUrl(self::DELETE_PROJECT_ENDPOINT);
        $data = compact('project');
        $response = $this->client->post($url, ['form_params' => $data]);

        return \GuzzleHttp\json_decode($response->getBody());
    }

    public function showLog($project, $spider, $jobid) {
        $url = $this->buildLogUrl($project, $spider, $jobid);
        $response = $this->client->get($url);

        return $response->getBody()->getContents();
    }
}
