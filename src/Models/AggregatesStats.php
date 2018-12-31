<?php

namespace App\Models;

trait AggregatesStats {

    // Individual items for stat gathering
    public $ip_addresses             = [];
    public $identds                  = [];
    public $userids                  = [];
    public $request_times            = [];
    public $request_methods          = [];
    public $request_uris             = [];
    public $response_codes           = [];
    public $response_code_categories = [];
    public $response_sizes           = [];
    public $http_referrers           = [];
    public $user_agents              = [];

    private function isSetOrIncrement($key, &$array) {
        if (empty($key)) {
            $key = "Unknown";
        }
        if (isset($array[$key])) {
            $array[$key]++;
        } else {
            $array[$key] = 1;
        }
    }

    private function sortByCount($a, $b) {
        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? 1 : -1;
    }

}