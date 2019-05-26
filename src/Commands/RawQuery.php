<?php

namespace App\Commands;

use App\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RawQuery extends Command {

    public function configure() {
        // General
        $this->setName('raw');
        $this->setDescription('Display a filtered set of lines from an Apache Access Log.');
        $this->setHelp('Will return all lines from Apache log that matches a given query');

        // Arguments
        $this->addArgument("path", InputArgument::REQUIRED, "The path to the access log to be parsed");

        // Options
        $this->addOption('log-type', null, InputOption::VALUE_OPTIONAL, 'Specify whether it is a common or combined formatted Apache log.', "combined");
        $this->addOption('ignore-agent', null, InputOption::VALUE_OPTIONAL, "Exclude all traffic that contains the Agent String you provide.", false);
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
        $query  = new Query($path);
        if ($input->getOption('ignore-agent')) {
            $query->ignoreAgent($input->getOption('ignore-agent'));
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
        $entries = $query->getRawEntries();
        if (empty($entries)) {
            stdOutError("No records match your criteria.", $output);
        }

        foreach ($entries as $line) {
            $output->write($line);
        }

        // Reportable information
        $query_line_count       = count($entries);
        $log_file_line_count    = $query->getLogLineCount();
        appLogInfo(trim(`whoami`)." performed raw query on {$path} which returned {$query_line_count} line out of {$log_file_line_count} total");

    }

}