<?php

namespace App\Commands;

use App\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UriOriginQuery extends Command {

    public function configure() {

        // General
        $this->setName('uri-origin');
        $this->setDescription('Display a list of all referrer traffic to a specified origin.');
        $this->setHelp('Will return a list of all referrer traffic to a case-insensitive uri search term.');
        // Arguments
        $this->addArgument("path", InputArgument::REQUIRED, "The path to the access log to be parsed");
        $this->addArgument("uri", InputArgument::REQUIRED, "Case insensitive term or full uri.");
        // Options
        $this->addOption('log-type', null, InputOption::VALUE_OPTIONAL, 'Specify whether it is a common or combined formatted Apache log.', "combined");
        $this->addOption("ignore-referrer", null,InputOption::VALUE_OPTIONAL, 'Ignore all referrers that match a given case insensitive uri.', false);

    }

    public function execute(InputInterface $input, OutputInterface $output) {

        $path   = $input->getArgument("path");
        $uri    = $input->getArgument("uri");
        if (!file_exists($path)) {
            appLogError("Unable to load file {$path} for query");
            stdOutErrorAndDie("Unable to load log file {$path}", $output);
        }
        $query  = new Query($path);
        if ($input->getOption('ignore-referrer')) {
            $query->ignoreReferrer($input->getOption('ignore-referrer'));
        }
        $query->searchUri($uri);

        $query->run($input->getOption('log-type'));
        if (count($query->getParsedEntries()) == 0) {
            stdOutError("No entries matched your URI search criteria.", $output); exit;
        }

        $referrers = $query->getReferrers();
        stdOutComment("Count\t\tUri", $output);
        foreach ($referrers as $target => $count) {
            stdOut("{$count}\t\t{$target}", $output);
        }

    }

}