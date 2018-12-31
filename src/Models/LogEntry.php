<?php

namespace App\Models;

use App\Config;

class LogEntry {

    // Raw line as text
    protected $line;
    // Pattern to use for extraction
    protected $pattern;
    // Fields in the log
    public $client_ip;
    public $identd;
    public $user_id;
    public $request_time;
    public $request_method;
    public $request_uri;
    public $response_code;
    public $response_size;
    // Fields ONLY IN COMBINED log
    public $referrer;
    public $user_agent;

    // Additional information about entry
    protected $parse_successful         = true;

    public $response_code_category      = "Unknown";
    public $is_bot_agent                = false;
    public $is_file_resource            = false;
    public $is_unusual_agent            = false;

    public function __construct($line, $pattern) {
        $this->line    = $line;
        $this->pattern = $pattern;
        $this->extract();
    }

    public function getLine() {
        return $this->line;
    }

    protected function extract() {
        // Extract Parts and shift off full match into nothing
        preg_match_all($this->pattern, $this->line, $parts);
        array_shift($parts);
        if (empty($parts[0][0])) {
            $this->parse_successful = false;
        } else {
            // Populate Model
            $this->client_ip        = $parts[0][0];
            $this->identd           = (trim($parts[1][0]) == "-") ? null : $parts[1][0];
            $this->user_agent       = (trim($parts[2][0]) == "-") ? null : $parts[2][0];
            $this->request_time     = \DateTime::createFromFormat('j/M/Y:H:i:s', $parts[3][0]);
            $this->request_method   = $parts[4][0];
            $this->request_uri      = $parts[5][0];
            if (preg_match(Config::get('resource.pattern'), $this->request_uri)) {
                $this->is_file_resource = true;
            }
            $this->response_code    = $parts[6][0];
            $this->response_size    = (trim($parts[7][0]) == "-") ? null : $parts[7][0];
            if (isset($parts[8][0])) {
                $this->referrer     = (trim($parts[8][0]) == "-") ? null : $parts[8][0];
            }
            if (isset($parts[9][0])) {
                if (preg_match(Config::get('unusual.agent.pattern'),$parts[9][0])) {
                    $this->is_unusual_agent = true;
                }
                if (preg_match(Config::get('bot.pattern'),$parts[9][0])) {
                    $this->is_bot_agent = true;
                }
                $this->user_agent = $parts[9][0];
            }
            // Perform additional operations / extractions
            $this->response_code_category = static::getErrorCodeCategory($this->response_code);
        }

    }

    public static function getErrorCodeCategory($code) {
        $code = (int) $code;
        $category = "Unknown";
        switch ($code) {
            case 200:
                $category = "OK"; break;
            case 201:
                $category = "Created"; break;
            case 202:
                $category = "Accepted"; break;
            case 203:
                $category = "Non-Authoritative Information"; break;
            case 204:
                $category = "No Content"; break;
            case 205:
                $category = "Reset Content"; break;
            case 206:
                $category = "Partial Content"; break;
            case 207:
                $category = "Multi-Status"; break;
            case 208:
                $category = "Already Reported"; break;
            case 226:
                $category = "IM Used"; break;
            case 300:
                $category = "Multiple Choices"; break;
            case 301:
                $category = "Moved Permanently"; break;
            case 302:
                $category = "Moved Temporarily"; break;
            case 303:
                $category = "See Other"; break;
            case 304:
                $category = "Not Modified"; break;
            case 305:
                $category = "Use Proxy"; break;
            case 306:
                $category = "Switch Proxy"; break;
            case 307:
                $category = "Temporary Redirect"; break;
            case 308:
                $category = "Permanent Redirect"; break;
            case 400:
                $category = "Bad Request"; break;
            case 401:
                $category = "Unauthorized"; break;
            case 402:
                $category = "Payment Required"; break;
            case 403:
                $category = "Forbidden"; break;
            case 404:
                $category = "Not Found"; break;
            case 405:
                $category = "Method Not Allowed"; break;
            case 406:
                $category = "Not Acceptable"; break;
            case 407:
                $category = "Proxy Authentication Required"; break;
            case 408:
                $category = "Request Timeout"; break;
            case 409:
                $category = "Conflict"; break;
            case 410:
                $category = "Gone"; break;
            case 411:
                $category = "Length Required"; break;
            case 412:
                $category = "Precondition Failed"; break;
            case 413:
                $category = "Payload Too Large"; break;
            case 414:
                $category = "URI Too Long"; break;
            case 415:
                $category = "Unsupported Media Type"; break;
            case 416:
                $category = "Range Not Satisfiable"; break;
            case 417:
                $category = "Expectation Failed"; break;
            case 418:
                $category = "I'm a teapot"; break;
            case 421:
                $category = "Misdirected Request"; break;
            case 422:
                $category = "Unprocessable Entity"; break;
            case 423:
                $category = "Locked"; break;
            case 424:
                $category = "Failed Dependency"; break;
            case 426:
                $category = "Upgrade Required"; break;
            case 428:
                $category = "Precondition Required"; break;
            case 429:
                $category = "Too Many Requests"; break;
            case 431:
                $category = "Request Header Fields Too Large"; break;
            case 451:
                $category = "Unavailable For Legal Reasons"; break;
            case 500:
                $category = "Internal Server Error"; break;
            case 501:
                $category = "Not Implemented"; break;
            case 502:
                $category = "Bad Gateway"; break;
            case 503:
                $category = "Service Unavailable"; break;
            case 504:
                $category = "Gateway Timeout"; break;
            case 505:
                $category = "HTTP Version Not Supported"; break;
            case 506:
                $category = "Variant Also Negotiates"; break;
            case 507:
                $category = "Insufficient Storage"; break;
            case 508:
                $category = "Loop Detected"; break;
            case 510:
                $category = "Not Extended"; break;
            case 511:
                $category = "Network Authentication Required"; break;

        }
        return $category;
    }

    public function parsedSuccessfully() {
        return $this->parse_successful;
    }
}