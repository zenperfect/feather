<?php

namespace App\Commands;

use App\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MalformedQuery extends Command {

    public function configure() {
        // General
        $this->setName('malformed-requests');
        $this->setDescription('Display a list of unparsed / malformed requests.');
        $this->setHelp('Will list raw requests which were unable to be parsed by feather');

        // Arguments
        $this->addArgument("path", InputArgument::REQUIRED, "The path to the access log to be parsed");

    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $path   = $input->getArgument("path");
        if (!file_exists($path)) {
            appLogError("Unable to load file {$path} for query");
            stdOutErrorAndDie("Unable to load log file {$path}", $output);
        }
        stdOut("<info>Collecting malformed requests...</info>", $output);
        $query  = new Query($path);
        $sum    = $query->getMalformedEntryCount();
        if ($sum > 0) {
            $lines  = $query->getMalformedEntries();
            foreach ($lines as $line) {
                stdOut($line, $output);
            }
        } else {
            stdOutError("No malformed entries found in log", $output);
        }
        // Reportable information
        $query_line_count       = $sum;
        $log_file_line_count    = $query->getLogLineCount();
        appLogInfo(trim(`whoami`)." performed graph query on {$path} which returned {$query_line_count} lines out of {$log_file_line_count} total");

    }

}