<?php

namespace App\Models;


use App\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CombinedAccessLog extends AccessLog {

    use AggregatesStats;

    protected static $pattern = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s(\-)\s(\-|.+)\s\[(\d{1,2}\/\D{1,3}\/\d{4}\:\d{1,2}\:\d{1,2}\:\d{1,2})\s\+\d{1,4}\]\s\"(GET|POST|HEAD|PUT|PATCH|\-)?\s?(\/\S+|\/)?\s?(?:HTTP\/\d{1}\.\d{1})?\"\s(\d{1,3})\s(\d{1,50}|\S)+\s\"(.+)?\"\s\"(.+|.?)?\"/';

    public function __construct($path, InputInterface $input, OutputInterface $output) {
        parent::__construct($path, $input, $output);

        $fh = fopen($path, "r");
        while (($line = fgets($fh)) !== false) {

            $entry              = new LogEntry($line, static::$pattern);
            $this->raw_lines[]  = $entry->getLine();

            // Check if parse is successful before adding
            if ($entry->parsedSuccessfully()) {
                // Account for input filters
                $config = Config::get('apache');

                // Check for date filter first
                // Format based on 20/Oct/2018:23:46:34
                $today  = new \DateTime('Now');
                if ($input->getOption('today')) {
                    if ($today->format('Y-m-d') !== $entry->request_time->format('Y-m-d')) {
                        continue;
                    }
                }

                // Check for this month filter
                if ($input->getOption('current-month')) {
                    if ($today->format('Y-m') !== $entry->request_time->format('Y-m')) {
                        continue;
                    }
                }

                // Check for 404 filter
                if ($input->getOption('not-found')) {
                    if ($entry->response_code != "404") {
                        continue;
                    }
                }

                // Check for unusual traffic filter
                if ($input->getOption('unusual-agents')) {
                    if (!$entry->is_unusual_agent) {
                        continue;
                    }
                }

                // Check for response code filter
                if ($input->getOption('response-code')) {
                    if ($entry->response_code != $input->getOption('response-code')) {
                        continue;
                    }
                }

                // Check for user agent filter
                if ($input->getOption('exclude-agent')) {
                    if (strpos($entry->user_agent, $input->getOption('exclude-agent')) !== false) {
                        continue;
                    }
                }

                // Check for bot user agent filter
                if ($input->getOption('exclude-bots')) {
                    if ($entry->is_bot_agent) {
                        continue;
                    }
                }

                // Check if only showing resources
                if ($input->getOption('only-resources')) {
                    if (!$entry->is_file_resource) {
                        continue;
                    }
                }

                // Check if excluding static resources
                if ($input->getOption('no-resources')) {
                    if ($entry->is_file_resource) {
                        continue;
                    }
                }

                // Check "this" option filters
                if ($input->getOption('this-agent')) {
                    $agent = $input->getOption('this-agent');
                    if (strpos(strtolower($entry->user_agent), strtolower($agent)) === false) {
                        continue;
                    }
                }

                if ($input->getOption('this-referrer')) {
                    $ref = $input->getOption('this-referrer');
                    if (strpos(strtolower($entry->referrer), strtolower($ref)) === false) {
                        continue;
                    }
                }

                if ($input->getOption('this-uri')) {
                    $uri = $input->getOption('this-uri');
                    if (strpos(strtolower($entry->request_uri), strtolower($uri)) === false) {
                        continue;
                    }
                }

                if ($input->getOption('this-ip')) {
                    $ip = $input->getOption('this-ip');
                    if (strpos($entry->client_ip, $ip) === false) {
                        continue;
                    }
                }

                $this->isSetOrIncrement($entry->client_ip, $this->ip_addresses);
                $this->isSetOrIncrement($entry->identd, $this->identds);
                $this->isSetOrIncrement($entry->user_id, $this->userids);
                $this->isSetOrIncrement($entry->request_time->format('Y-m-d H:i:s'), $this->request_times);
                $this->isSetOrIncrement($entry->request_method, $this->request_methods[]);
                $this->isSetOrIncrement($entry->request_uri, $this->request_uris);
                $this->isSetOrIncrement($entry->response_code,$this->response_codes);
                $this->isSetOrIncrement($entry->response_code_category,$this->response_code_categories);
                $this->isSetOrIncrement($entry->response_size,$this->response_sizes);
                $this->isSetOrIncrement($entry->referrer,$this->http_referrers);
                $this->isSetOrIncrement($entry->user_agent,$this->user_agents);
                array_push($this->entries, $entry);
                array_push($this->raw_entry_lines, $entry->getLine());
            } else {
                array_push($this->error_lines, $entry->getLine());
            }
        }

        // Sort all numerical statistic arrays by count
        uasort($this->ip_addresses,     array($this, 'sortByCount'));
        uasort($this->identds,          array($this, 'sortByCount'));
        uasort($this->userids,          array($this, 'sortByCount'));
        uasort($this->request_times,    array($this, 'sortByCount'));
        uasort($this->request_methods,  array($this, 'sortByCount'));
        uasort($this->request_uris,     array($this, 'sortByCount'));
        uasort($this->response_codes,   array($this, 'sortByCount'));
        uasort($this->response_sizes,   array($this, 'sortByCount'));
        uasort($this->http_referrers,   array($this, 'sortByCount'));
        uasort($this->user_agents,      array($this, 'sortByCount'));

        if (!empty($this->error_lines)) {
            array_map(function($line) {return trim($line);}, $this->error_lines);
            array_unique($this->error_lines, SORT_STRING);
        }
    }

}