<?php

namespace App;

use App\Models\LogEntry;

class Query {

    // Log information
    protected $path;
    protected $real_line_count          = 0;
    protected $raw_entries              = [];
    protected $parsed_entries           = [];
    protected $malformed_entries        = [];
    // Parsing Patterns
    protected $combined_pattern         = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s(\-)\s(\-|.+)\s\[(\d{1,2}\/\D{1,3}\/\d{4}\:\d{1,2}\:\d{1,2}\:\d{1,2})\s\+\d{1,4}\]\s\"(GET|POST|HEAD|PUT|PATCH|\-)?\s?(\/\S+|\/)?\s?(?:HTTP\/\d{1}\.\d{1})?\"\s(\d{1,3})\s(\d{1,50}|\S)+\s\"(.+)?\"\s\"(.+|.?)?\"/';
    protected $common_pattern           = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s(\-)\s(\-|.+)\s\[(\d{1,2}\/\D{1,3}\/\d{4}\:\d{1,2}\:\d{1,2}\:\d{1,2})\s\+\d{1,4}\]\s\"(GET|POST|HEAD|PUT|PATCH|\-)?\s?(\/\S+|\/)?\s?(?:HTTP\/\d{1}\.\d{1})?\"\s(\d{1,3})\s(\d{1,50}|\S)+/';
    // Query Ignore Criteria
    protected $ignore_files             = false;
    protected $ignore_bots              = false;
    protected $ignored_ip               = null;
    protected $ignored_uri              = null;
    protected $ignored_referrer         = null;
    protected $ignored_agent            = null;
    protected $current_year             = false;
    protected $current_month            = false;
    protected $current_date             = false;
    // Query Search Target Criteria
    protected $target_files             = false;
    protected $target_bots              = false;
    protected $target_ip                = "";
    protected $target_uri               = "";
    protected $target_referrer          = "";
    protected $target_agent             = "";
    protected $target_unusual_agents    = false;
    protected $target_response_code     = false;
    protected $target_successful_code   = false;
    protected $target_redirection_codes = false;
    protected $target_server_errors     = false;
    protected $target_client_errors     = false;

    // Parsed Query Stats
    protected $stat_ip_addresses             = [];
    protected $stat_identds                  = [];
    protected $stat_userids                  = [];
    protected $stat_request_times            = [];
    protected $stat_request_methods          = [];
    protected $stat_request_uris             = [];
    protected $stat_response_codes           = [];
    protected $stat_response_code_categories = [];
    protected $stat_response_sizes           = [];
    protected $stat_http_referrers           = [];
    protected $stat_user_agents              = [];
    // Debug stats
    protected $query_time_start;
    protected $query_time_end;
    protected $query_memory_start;
    protected $query_memory_end;

    public function __construct($path) {
        $this->path     = $path;
    }

    public function run($pattern = "combined") {
        $regex          = ($pattern == "combined") ? $this->combined_pattern : $this->common_pattern;
        // Begin query
        $fh = fopen($this->path, "r");
        $this->query_time_start     = microtime(true);
        $this->query_memory_start   = memory_get_peak_usage(true);
        while (($line = fgets($fh)) !== false) {
            $this->real_line_count++;
            $entry = new LogEntry($line, $regex);
            if ($entry->parsedSuccessfully()) {

                // Inspect all entries for inclusion or exclusion
                if ($this->ignore_files && $entry->is_file_resource) {
                    continue;
                }

                if ($this->target_files && !$entry->is_file_resource) {
                    continue;
                }

                if ($this->ignore_bots && $entry->is_bot_agent) {
                    continue;
                }

                if ($this->target_bots && !$entry->is_bot_agent) {
                    continue;
                }

                if (!empty($this->ignored_ip) && ($this->ignored_ip == $entry->client_ip)) {
                    continue;
                }

                if (!empty($this->ignored_uri) && (strpos($entry->request_uri, $this->ignored_uri) !== false)) {
                    continue;
                }

                if (!empty($this->ignored_referrer) && (strpos($entry->referrer, $this->ignored_referrer) !== false)) {
                    continue;
                }

                if (!empty($this->ignored_agent) && (strpos($entry->user_agent, $this->ignored_agent) !== false)) {
                    continue;
                }

                $today = new \DateTime('Now');

                if ($this->current_year && !($entry->request_time->format('Y') == $today->format('Y'))) {
                    continue;
                }

                if ($this->current_month && !($entry->request_time->format('Y-m') == $today->format('Y-m'))) {
                    continue;
                }

                if ($this->current_date && !($entry->request_time->format('Y-m-d') == $today->format('Y-m-d'))) {
                    continue;
                }

                if (!empty($this->target_ip) && !($this->target_ip == $entry->client_ip)) {
                    continue;
                }

                if (!empty($this->target_uri) && !(strpos($entry->request_uri, $this->target_uri) !== false)) {
                    continue;
                }

                if (!empty($this->target_referrer) && !(strpos($entry->referrer, $this->target_referrer) !== false)) {
                    continue;
                }

                if (!empty($this->target_agent) && !(strpos($entry->user_agent, $this->target_agent) !== false)) {
                    continue;
                }

                if ($this->target_unusual_agents && !$entry->is_unusual_agent) {
                    continue;
                }

                if ($this->target_response_code && ( (int) $entry->response_code !== $this->target_response_code)) {
                    continue;
                }

                if ($this->target_successful_code && (int) $entry->response_code !== 200) {
                    continue;
                }

                if ($this->target_redirection_codes && !((int) $entry->response_code >= 300 && (int) $entry->response_code < 400 )) {
                    continue;
                }

                if ($this->target_client_errors && !((int) $entry->response_code >= 400 && (int) $entry->response_code < 500 )) {
                    continue;
                }

                if ($this->target_server_errors && !((int) $entry->response_code >= 500 && (int) $entry->response_code < 600 )) {
                    continue;
                }

                $this->isSetOrIncrement($entry->client_ip, $this->stat_ip_addresses);
                $this->isSetOrIncrement($entry->identd, $this->stat_identds);
                $this->isSetOrIncrement($entry->user_id, $this->stat_userids);
                $this->isSetOrIncrement($entry->request_time->format('Y-m-d H:i:s'), $this->stat_request_times);
                $this->isSetOrIncrement($entry->request_method, $this->stat_request_methods[]);
                $this->isSetOrIncrement($entry->request_uri, $this->stat_request_uris);
                $this->isSetOrIncrement($entry->response_code,$this->stat_response_codes);
                $this->isSetOrIncrement($entry->response_code_category,$this->stat_response_code_categories);
                $this->isSetOrIncrement($entry->response_size,$this->stat_response_sizes);
                if ($pattern == "combined") {
                    $this->isSetOrIncrement($entry->referrer,$this->stat_http_referrers);
                    $this->isSetOrIncrement($entry->user_agent,$this->stat_user_agents);
                }
                array_push($this->parsed_entries, $entry);
                array_push($this->raw_entries, $entry->getLine());

            } else {
                array_push($this->malformed_entries, $line);
            }
        }
        // Sort all numerical statistic arrays by count
        uasort($this->stat_ip_addresses,     array($this, 'sortByCount'));
        uasort($this->stat_identds,          array($this, 'sortByCount'));
        uasort($this->stat_userids,          array($this, 'sortByCount'));
        uasort($this->stat_request_times,    array($this, 'sortByCount'));
        uasort($this->stat_request_methods,  array($this, 'sortByCount'));
        uasort($this->stat_request_uris,     array($this, 'sortByCount'));
        uasort($this->stat_response_codes,   array($this, 'sortByCount'));
        uasort($this->stat_response_sizes,   array($this, 'sortByCount'));
        if ($pattern == "combined") {
            uasort($this->stat_http_referrers,   array($this, 'sortByCount'));
            uasort($this->stat_user_agents,      array($this, 'sortByCount'));
        }
        if (!empty($this->malformed_entries)) {
            array_map(function($line) {return trim($line);}, $this->malformed_entries);
            array_unique($this->malformed_entries, SORT_STRING);
        }
        $this->query_time_end       = microtime(true);
        $this->query_memory_end     = memory_get_peak_usage(true);
    }

    public function ignoreBots() {
        $this->ignore_bots = true;
    }

    public function onlyBots() {
        $this->target_bots = true;
    }

    public function ignoreFiles() {
        $this->ignore_files = true;
    }

    public function onlyFiles() {
        $this->target_files = true;
    }

    public function ignoreIp($ip) {
        $this->ignored_ip = $ip;
    }

    public function ignoreUri($uri) {
        $this->ignored_uri = $uri;
    }

    public function ignoreReferrer($referrer) {
        $this->ignored_referrer = $referrer;
    }

    public function ignoreAgent($agent) {
        $this->ignored_agent = $agent;
    }

    public function searchIp($ip) {
        $this->target_ip = $ip;
    }

    public function searchUri($uri) {
        $this->target_uri = $uri;
    }

    public function searchReferrer($referrer) {
        $this->target_referrer = $referrer;
    }

    public function searchAgent($agent) {
        $this->target_agent = $agent;
    }

    public function unusualAgents() {
        $this->target_unusual_agents = true;
    }

    public function thisYear() {
        $this->current_year = true;
    }

    public function thisMonth() {
        $this->current_month = true;
    }

    public function today() {
        $this->current_date = true;
    }

    public function responseCode($int) {
        $this->target_response_code = (int) $int;
    }

    public function successCode() {
        $this->target_successful_code = true;
    }

    public function redirectionCodes() {
        $this->target_redirection_codes = true;
    }

    public function serverErrors() {
        $this->target_server_errors = true;
    }

    public function clientErrors() {
        $this->target_client_errors = true;
    }

    public function getParsedEntries() {
        return $this->parsed_entries;
    }

    public function getParsedEntryCount() {
        return count($this->parsed_entries);
    }

    public function getRawEntries() {
        return $this->raw_entries;
    }

    public function getRawEntryCount() {
        return count($this->raw_entries);
    }

    public function getMalformedEntries() {
        return $this->malformed_entries;
    }

    public function getMalformedEntryCount() {
        return count($this->malformed_entries);
    }

    public function getLogLineCount() {
        return $this->real_line_count;
    }

    public function getIpStats() {
        return $this->stat_ip_addresses;
    }

    public function getIdentDs() {
        return $this->stat_identds;
    }

    public function getUserIds() {
        return $this->stat_userids;
    }

    public function getRequestTimes() {
        return $this->stat_request_times;
    }

    public function getRequestMethods() {
        return $this->stat_request_methods;
    }

    public function getRequestUris() {
        return $this->stat_request_uris;
    }

    public function getResponseCodes() {
        return $this->stat_response_codes;
    }

    public function getResponseCodeCategories() {
        return $this->stat_response_code_categories;
    }

    public function getResponseSizes() {
        return $this->stat_response_sizes;
    }

    public function getReferrers() {
        return $this->stat_http_referrers;
    }

    public function getUserAgents() {
        return $this->stat_user_agents;
    }

    public function getQueryTime() {
        return round($this->query_time_end - $this->query_time_end, 2);
    }

    public function getQueryMemory() {
        return $this->convertBytes($this->query_memory_end - $this->query_memory_start);
    }

    private function convertBytes($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }

    private function isSetOrIncrement($key, &$array) {
        if (empty($key)) {
            $key = "Unknown";
        }
        if (isset($array[$key])) {
            $array[$key]++;
        } else {
            $array[$key] = 1;
        }
    }

    private function sortByCount($a, $b) {
        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? 1 : -1;
    }


}