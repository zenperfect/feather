<?php

namespace App\Commands;

use App\Feather;
use App\Models\CombinedAccessLog;
use App\Models\CommonAccessLog;
use App\Models\LogEntry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AccessLogCommand extends Command {


    protected $path;
    protected $log_format   = "combined";
    protected $log_formats  = [
        "combined"  => CombinedAccessLog::class,
        "common"    => CommonAccessLog::class
    ];
    // Filtered set based on user input
    protected $log;
    // Stat report specific options
    protected $count_displayed  = 10;

    protected function configure() {
        $this->setName('access-stats');
        $this->setDescription('Display useful stats from provided Apache access log.');
        $this->setHelp('Will display statistics from a default or combined Apache Access Log file.');
        // ARGUMENTS
        $this->addArgument("path", InputArgument::REQUIRED, "The path to the access log to be parsed");

        // OPTIONS
        $this->addOption("count", "c", InputOption::VALUE_OPTIONAL, "Number of records to return from each analytic", 10);
        $this->addOption('errors', 'e', InputOption::VALUE_NONE, "Show raw lines that were not parsed");
        $this->addOption("format", "f", InputOption::VALUE_OPTIONAL, "Combined or common log format", "combined");
        $this->addOption('json', 'j', InputOption::VALUE_NONE, 'Output report data as JSON for further processing');
        $this->addOption('raw-entries', 'r', InputOption::VALUE_NONE, 'Include raw lines associated with query');
        $this->addOption('file-report', 'z', InputOption::VALUE_NONE, 'Export as JSON report to file');
        $this->addOption('exclude-agent', 'A', InputOption::VALUE_OPTIONAL, "Exclude user containing the string you provide.", false);
        $this->addOption('exclude-bots', 'B', InputOption::VALUE_NONE, "Exclude traffic from bots and spiders");
        $this->addOption('current-month', 'M', InputOption::VALUE_NONE, "Only show this month's data in the output");
        $this->addOption('not-found', 'N', InputOption::VALUE_NONE, "Only show 404s");
        $this->addOption('no-resources', 'R', InputOption::VALUE_NONE, "Exclude requests for static resources such as css, js, jpg, etc");
        $this->addOption('only-resources', null, InputOption::VALUE_NONE, "Only show requests for static resources such as css, js, jpg, etc");
        $this->addOption('today', 'T', InputOption::VALUE_NONE, "Only show today's data in the output");
        $this->addOption('unusual-agents', 'U', InputOption::VALUE_NONE, "Only show unusual agent traffic");
        $this->addOption('response-code', 'X', InputOption::VALUE_OPTIONAL, 'Only show based on a HTTP response code');
        // Case insensitive search and match filters
        $this->addOption('this-agent', null, InputOption::VALUE_OPTIONAL, 'Display only traffic from a specific user agent using case-insensitive search term', false);
        $this->addOption('this-referrer', null, InputOption::VALUE_OPTIONAL, 'Display only traffic from a specific referrer using case-insensitive search term', false);
        $this->addOption('this-uri', null, InputOption::VALUE_OPTIONAL, 'Display only traffic to a specific URI using case-insensitive search term', false);
        $this->addOption('this-ip', null, InputOption::VALUE_OPTIONAL, 'Display only traffic from a specific IP address', false);
        // Output data control
        $this->addOption('only-agent', null, InputOption::VALUE_OPTIONAL, 'Only output user agent data from query', false);
        $this->addOption('only-referrer', null, InputOption::VALUE_OPTIONAL, 'Only output referrer data from query', false);
        $this->addOption('only-uri', null, InputOption::VALUE_OPTIONAL, 'Only output URI data from query', false);
        $this->addOption('only-ip', null, InputOption::VALUE_OPTIONAL, 'Only output IP addresse data from query', false);

    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        // Setup Report variables
        $this->path                 = $input->getArgument('path');
        $this->log_format           = ($input->getOption('format')) ? $input->getOption('format') : "combined";
        $this->count_displayed      = ($input->getOption('count')) ? $input->getOption('count') : 10;

        if (!file_exists($this->path)) {
            stdOutErrorAndDie("<error>Unable to load file at: {$this->path}</error>", $output);
        }

        if (!isset($this->log_formats[$this->log_format])) {
            stdOutErrorAndDie("Unknown format provided. It must be either 'combined' or 'common'.", $output);
        }

        if ($input->getOption('only-resources') && $input->getOption('no-resources')) {
            stdOutErrorAndDie("Cannot use no-resources and only-resources options together!", $output);
        }

        if ($this->log_format == "common" && ($input->getOption('this-agent') || $input->getOption('this-referrer'))) {
            stdOutErrorAndDie("User agents and referrers are not available in a common apache access log.", $output);
        }

        $this->log = new $this->log_formats[$this->log_format]($this->path, $input, $output);

        if ($input->getOption('file-report')) {
            $this->sendToFile($input);
        }
        if ($input->getOption('json')) {
            $this->displayAsJSON($input, $output);
        } else {
            $this->displayToConsole($input, $output);
        }
    }

    private function displayToConsole(InputInterface $input, OutputInterface $output) {

        if (empty($this->log->getEntries())) {
            stdOutError("No records match your criteria.", $output);
        }

        stdOutComment("Showing the top {$this->count_displayed} for all stats.", $output);

        // START URI STATS =====================================================
        stdOutInfo("URIs: (" . count($this->log->request_uris).") unique URIs", $output);
        $uris = 0;
        foreach ($this->log->request_uris as $uri => $count) {
            if ($uris > $this->count_displayed) {
                break;
            }
            stdOut("{$count}\t\t$uri", $output);
            $uris++;
        }
        stdOutDivider($output);
        // END URI STATS ========================================================


        // START RESPONSE CODE STATS ============================================
        stdOutInfo("Response Codes: (" . count($this->log->response_codes).") unique response codes", $output);
        $codes = 0;
        foreach ($this->log->response_codes as $code => $count) {
            if ($codes > $this->count_displayed) {
                break;
            }
            if ($output->isVerbose()) {
                stdOut("{$count}\t\t$code (".LogEntry::getErrorCodeCategory($code).")", $output);
            } else {
                stdOut("{$count}\t\t$code", $output);
            }

            $codes++;
        }
        stdOutDivider($output);
        // END RESPONSE CODE STATS ==============================================


        // START IP ADDRESS STATS ===============================================
        stdOutInfo("IP Addresses: (" . count($this->log->ip_addresses).") unique IPs", $output);
        $ips = 0;
        foreach ($this->log->ip_addresses as $ip => $count) {
            if ($ips > $this->count_displayed) {
                break;
            }
            stdOut("{$count}\t\t$ip", $output);
            $ips++;
        }
        stdOutDivider($output);
        // END IP ADDRESS STATS ==================================================


        if (get_class($this->log) == CombinedAccessLog::class) {
            // START REFERRER STATS ==================================================
            stdOutInfo("Referrers: (" . count($this->log->http_referrers).") unique referrers", $output);
            $uris = 0;
            foreach ($this->log->http_referrers as $uri => $count) {
                if ($uris > $this->count_displayed) {
                    break;
                }
                stdOut("{$count}\t\t$uri", $output);
                $uris++;
            }
            // END REFERRER STATS ====================================================
            stdOutDivider($output);

            // START USER AGENT STATS ================================================
            stdOutInfo("User Agents: (" . count($this->log->user_agents).") unique user agents", $output);
            $agents = 0;
            foreach ($this->log->user_agents as $agent => $count) {
                if ($agents > $this->count_displayed) {
                    break;
                }
                stdOut("{$count}\t\t$agent", $output);
                $agents++;
            }
            // END USER AGENT STATS ==================================================
            stdOutDivider($output);
        }

        // FINAL STATS
        stdOutInfo("Total Lines in Log: ".count($this->log->getRawLines()), $output);
        stdOutInfo("Total Entries in Query: ".count($this->log->getEntries()), $output);
        Feather::log()->addNotice(`whoami`." scanned apache access log: {$this->path}");

        // START ENTRY LINE OUTPUT ============================================
        if (count($this->log->getRawEntryLines()) > 0) {
            if ($input->getOption('raw-entries')) {
                stdOutInfo("Lines from log associated with your query:", $output);
                stdOutComment("Total Parsed Raw lines: ".count($this->log->getRawEntryLines()), $output);
                foreach ($this->log->getRawEntryLines() as $line) {
                    stdOut(trim($line), $output);
                }
                stdOutDivider($output);
            }
        }
        // END ENTRY LINE OUTPUT ===============================================

        // START UN-PARSED LINE STATS ============================================
        if (count($this->log->getErrorLines()) > 0) {
            if ($input->getOption('errors')) {
                stdOutError("Lines that were not parsed properly:", $output);
                stdOutComment("Total Error lines: ".count($this->log->getErrorLines()), $output);
                foreach ($this->log->getErrorLines() as $error_line) {
                    stdOut(trim($error_line), $output);
                }
                stdOutDivider($output);
            } else {
                // Show error count without content
                stdOutError("Total Error lines (Not Displayed): ".count($this->log->getErrorLines()), $output);
            }
        }
        // END UN-PARSED LINE STATS ===============================================

    }

    private function displayAsJSON(InputInterface $input, OutputInterface $output) {

        if (empty($this->log->getEntries())) {
            stdOut(json_encode([], JSON_PRETTY_PRINT), $output);exit;
        }
        stdOut($this->getDataAsJSON(), $output);

    }

    private function getDataAsJSON() {

        return json_encode([
            'uris'              => $this->returnLimitedStat($this->log->request_uris, $this->count_displayed),
            'response.codes'    => $this->returnLimitedStat($this->log->response_codes, $this->count_displayed),
            'ip.addresses'      => $this->returnLimitedStat($this->log->ip_addresses, $this->count_displayed),
            'referrers'         => $this->returnLimitedStat($this->log->http_referrers, $this->count_displayed),
            'user.agents'       => $this->returnLimitedStat($this->log->user_agents, $this->count_displayed)
        ], JSON_PRETTY_PRINT);

    }

    private function sendToFile(InputInterface $input) {
        $data = [
            'title'             => "Apache Access Log Report of {$this->path}",
            'args'              => $input->getArguments(),
            'options'           => $input->getOptions(),
	    'user'		=> `whoami`,
            'uris'              => $this->returnLimitedStat($this->log->request_uris, $this->count_displayed),
            'response.codes'    => $this->returnLimitedStat($this->log->response_codes, $this->count_displayed),
            'ip.addresses'      => $this->returnLimitedStat($this->log->ip_addresses, $this->count_displayed),
            'referrers'         => $this->returnLimitedStat($this->log->http_referrers, $this->count_displayed),
            'user.agents'       => $this->returnLimitedStat($this->log->user_agents, $this->count_displayed),
        ];
        $report_name = trim(str_replace("/",".", $this->path), ".");
        $handle = fopen(REPORTS_DIR.$report_name.".apache.stats.json", "w");
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
        fclose($handle);
    }

    private function returnLimitedStat($array, $limit) {
        $i      = 0;
        $set    = [];
        foreach ($array as $stat => $count) {
            $set[$stat] = $count;
            $i++;
            if ($i >= $limit) {
                break;
            }
        }
        return $set;
    }

}
