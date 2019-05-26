<?php

use Symfony\Component\Console\Output\OutputInterface;

// Standard Output Shortcuts

function stdOut($message,OutputInterface $output) {
    $output->writeln("{$message}");
}

function stdOutNewline(OutputInterface $output) {
    $output->writeln("");
}

function stdOutDivider(OutputInterface $output, $count = 20) {
    $output->writeln("=".str_repeat("=", $count)."=");
}

function stdOutInfo($message,OutputInterface $output) {
    $output->writeln("<info>{$message}</info>");
}

function stdOutComment($message,OutputInterface $output) {
    $output->writeln("<comment>{$message}</comment>");
}

function stdOutQuestion($message,OutputInterface $output) {
    $output->writeln("<question>{$message}</question>");
}

function stdOutError($message,OutputInterface $output) {
    $output->writeln("<error>{$message}</error>");
}

function stdOutErrorAndDie($message,OutputInterface $output) {
    $output->writeln("<error>{$message}</error>"); exit;
}

// Logging functions

function appLog($level, $message) {
    if (!file_exists(EVENT_LOG)) {
        $handle = fopen(EVENT_LOG, "w");
        fclose($handle);
    }
    $handle = fopen(EVENT_LOG, "a");
    $time   = (new \DateTime('Now'))->format('Y-m-d H:i:s');
    $output = "[{$time}] {$level} -> {$message}\n";
    fwrite($handle, $output);
    fclose($handle);
}

function appLogEmergency($message) {
    appLog("EMERGENCY", $message);
}

function appLogAlert($message) {
    appLog("ALERT", $message);
}

function appLogCritical($message) {
    appLog("CRITICAL", $message);
}

function appLogError($message) {
    appLog("ERROR", $message);
}

function appLogWarning($message) {
    appLog("WARNING", $message);
}

function appLogNotice($message) {
    appLog("NOTICE", $message);
}

function appLogInfo($message) {
    appLog("INFO", $message);
}

function appLogDebug($message) {
    appLog("DEBUG", $message);
}