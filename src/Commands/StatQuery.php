<?php

namespace App\Commands;

use App\Models\LogEntry;
use App\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatQuery extends Command {

    public function configure() {
        // General
        $this->setName('stats');
        $this->setDescription('Display a summarized overview of all data contained in an Apache Access Log.');
        $this->setHelp('Will return a list of stats by IP Address, Response Code, URI, Referrer');

        // Arguments
        $this->addArgument("path", InputArgument::REQUIRED, "The path to the access log to be parsed");

        // Options
        $this->addOption("count", "c", InputOption::VALUE_OPTIONAL, "Number of records to print to console from each analytic.", 10);
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON for further processing.');
        $this->addOption('log-type', null, InputOption::VALUE_OPTIONAL, 'Specify whether it is a common or combined formatted Apache log.', "combined");
        $this->addOption('ignore-agent', null, InputOption::VALUE_OPTIONAL, "Exclude all traffic that contains the Agent String you provide.", false);
        $this->addOption('ignore-referrer', null, InputOption::VALUE_OPTIONAL, "Exclude all traffic that contains the Referrer String you provide.", false);
        $this->addOption('ignore-bots', null, InputOption::VALUE_NONE, "Exclude all traffic from bots and spiders.");
        $this->addOption('only-bots', null, InputOption::VALUE_NONE, "Only display traffic from bots and spiders.");

        $this->addOption('ignore-files', null, InputOption::VALUE_NONE, "Exclude requests for static resources such as css, js, jpg files.");
        $this->addOption('only-files', null, InputOption::VALUE_NONE, "Only display requests for static resources such as css, js, jpg files.");
        $this->addOption('response-code', null, InputOption::VALUE_OPTIONAL, 'Only show traffic based on a HTTP response code.');
        $this->addOption('successful', null, InputOption::VALUE_NONE, "Only show 200 responses");
        $this->addOption('redirection', null, InputOption::VALUE_NONE, "Only show 30x responses");
        $this->addOption('not-found', null, InputOption::VALUE_NONE, "Only show 404 responses");
        $this->addOption('client-errors', null, InputOption::VALUE_NONE, "Only show 40x responses");
        $this->addOption('server-errors', null, InputOption::VALUE_NONE, "Only show 50x responses");
        $this->addOption('http-verb', null, InputOption::VALUE_OPTIONAL, "Only show traffic based on a HTTP request verb");
        $this->addOption('only-files', null, InputOption::VALUE_NONE, "Only show requests for static resources such as css, js, jpg files.");
        $this->addOption('unusual-agents', null, InputOption::VALUE_NONE, "Only show unusual agent traffic");
        $this->addOption('today', null, InputOption::VALUE_NONE, "Only show today's data in the output.");
        $this->addOption('current-month', null, InputOption::VALUE_NONE, "Only show this month's data in the output.");
        $this->addOption('current-year', null, InputOption::VALUE_NONE, "Only show this year's data in the output.");
        $this->addOption('this-agent', null, InputOption::VALUE_OPTIONAL, 'Display only traffic from a specific user agent using a case-insensitive search term.', false);
        $this->addOption('this-referrer', null, InputOption::VALUE_OPTIONAL, 'Display only traffic from a specific referrer using a case-insensitive search term.', false);
        $this->addOption('this-uri', null, InputOption::VALUE_OPTIONAL, 'Display only traffic to a specific URI using a case-insensitive search term.', false);
        $this->addOption('this-ip', null, InputOption::VALUE_OPTIONAL, 'Display only traffic from a specific IP address', false);

    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $path   = $input->getArgument("path");
        if (!file_exists($path)) {
            appLogError("Unable to load file {$path} for query");
            stdOutErrorAndDie("Unable to load log file {$path}", $output);
        }
        if (!$input->getOption('json')) {
            stdOutInfo("Starting Query...", $output);
        }

        $query  = new Query($path);
        if ($input->getOption('ignore-agent')) {
            $query->ignoreAgent($input->getOption('ignore-agent'));
        }
        if ($input->getOption('ignore-referrer')) {
            $query->ignoreReferrer($input->getOption('ignore-referrer'));
        }
        if ($input->getOption('ignore-bots')) {
            $query->ignoreBots();
        }
        if ($input->getOption('only-bots')) {
            $query->onlyBots();
        }
        if ($input->getOption('ignore-files')) {
            $query->ignoreFiles();
        }
        if ($input->getOption('only-files')) {
            $query->onlyFiles();
        }
        if ($input->getOption('unusual-agents')) {
            $query->unusualAgents();
        }
        if ($input->getOption('response-code')) {
            $query->responseCode($input->getOption('response-code'));
        }
        if ($input->getOption('http-verb')) {
            $query->requestVerb($input->getOption('http-verb'));
        }
        if ($input->getOption('successful')) {
            $query->successCode();
        }
        if ($input->getOption('redirection')) {
            $query->redirectionCodes();
        }
        if ($input->getOption('not-found')) {
            $query->responseCode(404);
        }
        if ($input->getOption('client-errors')) {
            $query->clientErrors();
        }
        if ($input->getOption('server-errors')) {
            $query->serverErrors();
        }
        if ($input->getOption('today')) {
            $query->today();
        }
        if ($input->getOption('current-month')) {
            $query->thisMonth();
        }
        if ($input->getOption('current-year')) {
            $query->thisYear();
        }
        if ($input->getOption('this-agent')) {
            $query->searchAgent($input->getOption('this-agent'));
        }
        if ($input->getOption('this-referrer')) {
            $query->searchReferrer($input->getOption('this-referrer'));
        }
        if ($input->getOption('this-uri')) {
            $query->searchUri($input->getOption('this-uri'));
        }
        if ($input->getOption('this-ip')) {
            $query->searchIp($input->getOption('this-ip'));
        }

        $query->run($input->getOption('log-type'));
        $entries = $query->getParsedEntries();
        if (empty($entries)) {
            stdOutError("No records match your criteria.", $output);
        }

        if ($input->getOption('json')) {
            // Output as JSON
            $data = [
                "uris"              => $query->getRequestUris(),
                "ips"               => $query->getIpStats(),
                "response.codes"    => $query->getResponseCodes(),
                "referrers"         => $query->getReferrers(),
                "user.agents"       => $query->getUserAgents()
            ];
            stdOut(json_encode($data, JSON_PRETTY_PRINT), $output);
        } else {
            stdOutInfo("Records found in query: ".$query->getParsedEntryCount()." out of a total of ".$query->getLogLineCount()." lines", $output);

            // URIs Section
            stdOutComment("URIs\n", $output);
            $table_uri = new Table($output);
            $num_uris = count($query->getRequestUris());
            //stdOutComment("URIs: ({$num_uris})", $output);
            $table_uri->setHeaders(['Count', 'URI']);
            $uris = 0;
            foreach ($query->getRequestUris() as $uri => $count) {
                if ($uris >= $input->getOption('count')) {
                    break;
                }
                //stdOut("{$count}\t\t$uri", $output);
                $table_uri->addRow([$count, $uri]);
                $uris++;
            }
            $table_uri->setStyle('compact')->render();
            stdOutNewline($output);

            // IPs Section
            stdOutComment("IP Addresses\n", $output);
            $table_ips = new Table($output);
            $num_ips = count($query->getIpStats());
            //stdOutComment("IP Addresses: ({$num_ips})", $output);
            $table_ips->setHeaders(['Count', 'IP Address']);
            $ips = 0;
            foreach ($query->getIpStats() as $ip => $count) {
                if ($ips >= $input->getOption('count')) {
                    break;
                }
                //stdOut("{$count}\t\t$ip", $output);
                $table_ips->addRow([$count, $ip]);
                $ips++;
            }
            $table_ips->setStyle('compact')->render();
            stdOutNewline($output);

            // Request Verb Section
            stdOutComment("Request Methods\n", $output);
            $table_rm = new Table($output);
            $table_rm->setHeaders(['Count', 'Request Method']);
            $methods = 0;
            foreach ($query->getRequestMethods() as $method => $count) {
                if ($methods >= $input->getOption('count')) {
                    break;
                }
                $table_ips->addRow([$count, $method]);
                $methods++;
            }
            $table_rm->setStyle('compact')->render();
            stdOutNewline($output);

            // Response Code Section
            stdOutComment("Response Codes\n", $output);
            $table_rc = new Table($output);
            $num_codes = count($query->getResponseCodes());
            //stdOutComment("Response Codes: ({$num_codes})", $output);
            $table_rc->setHeaders(['Count', 'Response Code']);
            $codes = 0;
            foreach ($query->getResponseCodes() as $code => $count) {
                if ($codes >= $input->getOption('count')) {
                    break;
                }
                //stdOut("{$count}\t\t$code", $output);
                $table_rc->addRow([$count, "{$code} (".LogEntry::getErrorCodeCategory($code).")"]);
                $codes++;
            }
            $table_rc->setStyle('compact')->render();
            stdOutNewline($output);

            // Referrer section
            stdOutComment("HTTP Referrers\n", $output);
            $table_ref = new Table($output);
            $num_referrers = count($query->getReferrers());
            //stdOutComment("Referrers: ({$num_referrers})", $output);
            $table_ref->setHeaders(['Count', 'Referrer']);
            $referrers = 0;
            foreach ($query->getReferrers() as $ref => $count) {
                if ($referrers >= $input->getOption('count')) {
                    break;
                }
                //stdOut("{$count}\t\t$ref", $output);
                $table_ref->addRow([$count, $ref]);
                $referrers++;
            }
            $table_ref->setStyle('compact')->render();
            stdOutNewline($output);

            // User Agents section
            stdOutComment("User Agents\n", $output);
            $table_agents = new Table($output);
            $num_agents = count($query->getUserAgents());
            //stdOutComment("User Agents: ({$num_agents})", $output);
            $table_agents->setHeaders(['Count', 'User Agent']);
            $agents = 0;
            foreach ($query->getUserAgents() as $agent => $count) {
                if ($agents >= $input->getOption('count')) {
                    break;
                }
                //stdOut("{$count}\t\t$agent", $output);
                $table_agents->addRow([$count, $agent]);
                $agents++;
            }
            $table_agents->setStyle('compact')->render();
            stdOutNewline($output);

            if ($output->isVerbose()) {
                stdOutInfo("Query took {$query->getQueryTime()} seconds and used {$query->getQueryMemory()}", $output);
            }
        }
        appLogInfo(trim(`whoami`)." performed a stats query on {$path}");

    }

}