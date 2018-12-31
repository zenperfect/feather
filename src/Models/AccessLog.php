<?php

namespace App\Models;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AccessLog {

    protected $file         = null;
    protected $input;
    protected $output;
    protected $raw_lines        = [];
    protected $entries          = [];
    protected $raw_entry_lines  = [];
    protected $error_lines      = [];

    public function __construct($path, InputInterface $input, OutputInterface $output) {
        $this->file     = $path;
        $this->input    = $input;
        $this->output   = $output;
    }

    public function getRawLines() {
        return $this->raw_lines;
    }

    public function getEntries() {
        return $this->entries;
    }

    public function getErrorLines() {
        return $this->error_lines;
    }

    public function getRawEntryLines() {
        return $this->raw_entry_lines;
    }

}