<?php

namespace App;

use App\Commands\GraphQuery;
use App\Commands\LogSummaryQuery;
use App\Commands\MalformedQuery;
use App\Commands\RawQuery;
use App\Commands\StatQuery;
use App\Commands\UriOriginQuery;

class Feather {

    // Application information
    protected $application_name     = "Feather Toolkit";
    protected $application_version  = "0.0.5";
    // Shared assets
    protected $command_console;

    public function __construct() {
        Config::load();
        $this->initializeCommandConsole();
        appLogNotice(trim(`whoami`)." accessed CLI");
    }

    protected function initializeCommandConsole() {
        $this->command_console = new \Symfony\Component\Console\Application("Zen Perfect Design | {$this->application_name}");
        $this->command_console->setVersion($this->application_version);
        $this->command_console->add(new LogSummaryQuery());
        $this->command_console->add(new StatQuery());
        $this->command_console->add(new RawQuery());
        $this->command_console->add(new UriOriginQuery());
        $this->command_console->add(new GraphQuery());
        $this->command_console->add(new MalformedQuery());
    }

    public function console() {
        return $this->command_console;
    }

    public function run() {
        $this->command_console->run();
    }

}