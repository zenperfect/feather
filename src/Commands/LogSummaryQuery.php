<?php

namespace App\Commands;

use App\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogSummaryQuery extends Command {

    public function configure() {
        // General
        $this->setName('summary');
        $this->setDescription('Display a summary of data contained in an Apache Access Log.');
        $this->setHelp('Will display a brief summary of the contents of a specified Apache access Log');

        // Arguments
        $this->addArgument("path", InputArgument::REQUIRED, "The path to the access log to be parsed");

        // Options
        $this->addOption('log-type', null, InputOption::VALUE_OPTIONAL, 'Specify whether it is a common or combined formatted Apache log.', "combined");
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON for further processing.');

    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $path   = $input->getArgument("path");
        if (!file_exists($path)) {
            appLogError("Unable to load file {$path} for query");
            stdOutErrorAndDie("Unable to load log file {$path}", $output);
        }

        $log    = new \SplFileInfo($path);
        $query  = new Query($path);
        $query->run($input->getOption('log-type'));

        if ($input->getOption('json')) {
            // Output as JSON
            $data = [
                "filesize"              => $log->getSize(),
                "line-count"            => $query->getLogLineCount(),
                "unparsed-lines"        => $query->getMalformedEntryCount(),
                "unique-ips"            => count($query->getIpStats()),
                "unique-uris"           => count($query->getRequestUris()),
                "unique-referrers"      => count($query->getReferrers()),
                "unique-user-agents"    => count($query->getUserAgents()),
                "request-methods"       => implode(", ",array_keys($query->getRequestMethods()))
            ];
            stdOut(json_encode($data, JSON_PRETTY_PRINT), $output);
        } else {
            stdOut("<info>Collecting summary data...</info>", $output);
            $table  = new Table($output);
            $table->setHeaders(['Statistic', 'Details']);
            $table->addRow(['Filesize', $log->getSize()]);
            $table->addRow(['Starts On', $query->getLogEarliestDate()->format('Y-m-d H:i:s')]);
            $table->addRow(['Ends On', $query->getLogLatestDate()->format('Y-m-d H:i:s')]);
            $table->addRow(['Line Count', $query->getLogLineCount()]);
            $table->addRow(['Unparsed Lines',$query->getMalformedEntryCount()]);
            $table->addRow(['Unique IPs',count($query->getIpStats())]);
            $table->addRow(['Unique URIs', count($query->getRequestUris())]);
            $table->addRow(['Unique Referrers', count($query->getReferrers())]);
            $table->addRow(['Unique User Agents', count($query->getUserAgents())]);
            $table->addRow(['Request Methods', implode(", ",array_keys($query->getRequestMethods()))]);
            $table->setStyle('borderless')->render();
        }

        appLogInfo(trim(`whoami`)." performed log summary query on {$path}");

    }

}