<?php

namespace App;

class Config {

    protected static $config = [];

    public static function load() {
        static::$config = require_once CONFIG_FILE;
    }

    public static function get($index) {
        return (isset(static::$config[$index])) ? static::$config[$index] : false;
    }

    public static function write($data = []) {
        $handle     = fopen(CONFIG_FILE, "w");
        $raw_format = var_export($data, true);
        $output     = "<?php ".PHP_EOL.PHP_EOL."return ";
        $output    .= $raw_format.";";
        fwrite($handle, $output);
        fclose($handle);
    }

}