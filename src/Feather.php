<?php

namespace App;

use App\Commands\Stats;

class Feather {

    // Application information
    protected $application_name     = "Feather Toolkit";
    protected $application_version  = "0.0.1";
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
        $this->command_console->add(new Stats());
    }

    public function console() {
        return $this->command_console;
    }

    public function run() {
        $this->command_console->run();
    }

}