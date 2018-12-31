<?php

namespace App;

use App\Commands\AccessLogCommand;

class Feather {

    // Application information
    protected $application_name     = "Feather Toolkit";
    protected $application_version  = "0.0.1";
    // Shared assets
    protected $command_console;
    protected static $logger;

    public function __construct() {
        Config::load();
        $this->initializeLogger();
        $this->initializeCommandConsole();
    }

    protected function initializeLogger() {
        static::$logger   = new \Monolog\Logger($this->application_name." Log");
        try {
            $fh = new \Monolog\Handler\StreamHandler(EVENT_LOG);
            static::$logger->pushHandler($fh);
            static::$logger->addNotice(trim(`whoami`)." accessed Feather");
        } catch (\Exception $e) {
            die("Unable to create Feather Commandline Log at: ".EVENT_LOG);
        }
    }

    protected function initializeCommandConsole() {
        $this->command_console = new \Symfony\Component\Console\Application("Zen Perfect Design | {$this->application_name}");
        $this->command_console->setVersion($this->application_version);
        $this->command_console->add(new AccessLogCommand());
    }

    public function console() {
        return $this->command_console;
    }

    public function run() {
        $this->command_console->run();
    }

    public static function log() {
        return static::$logger;
    }

}