<?php

use Symfony\Component\Console\Output\OutputInterface;

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