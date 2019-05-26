# Feather Toolkit

Feather is a lightweight and intuitive CLI for parsing Apache logs on the fly to pull out useful statistics about your web traffic. The intent of this toolset is to provide a useful tool to monitor your Apache server traffic. All you need is PHP 7.1.3 or greater and composer and you are on your way to a powerful command-line pipeline.

## Getting Started

I recommend cloning a copy to your local machine first to get used to the commands. Since this is my first Github project, I will do my best to document everything as much as possible and I apologize ahead of time for my Github ignorance!


**List Commands**: 

```
php feather --list
```

This will provide a detailed list of all available commands a quick description of what they do. **Everything in Feather is non-destructive**. When gathering information from your server that generates additional or transformed information, that transformed information is stored in the app folder.

**Getting Command Help**:

```
php feather stats --help
```

This will list all of the arguments and options available when performing the stats command. Most of the options available stack on each other and help you further refine a search *e.g.*:

```
php feather stats /path/to/ssl.access_log --ignore-bots --ignore-files --today --ignore-agent Fedora --this-agent="python"
```

The above command will parse your ssl.access_log and then return all lines that are:
 
 - **No** BOTS (--ignore-bots)
 - **No** Files i.e. direct requests for files such as .js, .css, etc (--ignore-files), 
 - TODAY's (--today) traffic only
 - **No** AGENT (--ignore-agent) traffic with Fedora in the string
 - --this-agent Show only traffic where the agent contains the word python (case-insensitive)
 
You could optionally tag on an option to output it to JSON using (--json) to use as an application endpoint or push to a file. Making Feather an awesome solution to feed raw Apache data into your web app! 

## stats Command

```
Description:
  Display a summarized overview of all data contained in an Apache Access Log.

Usage:
  stats [options] [--] <path>

Arguments:
  path                                 The path to the access log to be parsed

Options:
  -c, --count[=COUNT]                  Number of records to print to console from each analytic. [default: 10]
      --json                           Output as JSON for further processing.
      --log-type[=LOG-TYPE]            Specify whether it is a common or combined formatted Apache log. [default: "combined"]
      --ignore-agent[=IGNORE-AGENT]    Exclude all traffic that contains the Agent String you provide. [default: false]
      --ignore-bots                    Exclude all traffic from bots and spiders.
      --only-bots                      Only display traffic from bots and spiders.
      --ignore-files                   Exclude requests for static resources such as css, js, jpg files.
      --only-files                     Only show requests for static resources such as css, js, jpg files.
      --response-code[=RESPONSE-CODE]  Only show traffic based on a HTTP response code.
      --successful                     Only show 200 responses
      --redirection                    Only show 30x responses
      --not-found                      Only show 404 responses
      --client-errors                  Only show 40x responses
      --server-errors                  Only show 50x responses
      --unusual-agents                 Only show unusual agent traffic
      --today                          Only show today's data in the output.
      --current-month                  Only show this month's data in the output.
      --current-year                   Only show this year's data in the output.
      --this-agent[=THIS-AGENT]        Display only traffic from a specific user agent using a case-insensitive search term. [default: false]
      --this-referrer[=THIS-REFERRER]  Display only traffic from a specific referrer using a case-insensitive search term. [default: false]
      --this-uri[=THIS-URI]            Display only traffic to a specific URI using a case-insensitive search term. [default: false]
      --this-ip[=THIS-IP]              Display only traffic from a specific IP address [default: false]
  -h, --help                           Display this help message
  -q, --quiet                          Do not output any message
  -V, --version                        Display this application version
      --ansi                           Force ANSI output
      --no-ansi                        Disable ANSI output
  -n, --no-interaction                 Do not ask any interactive question
  -v|vv|vvv, --verbose                 Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Will return a list of stats by IP Address, Response Code, URI, Referrer
```

## raw Command

```
Description:
  Display a filtered set of lines from an Apache Access Log.

Usage:
  raw [options] [--] <path>

Arguments:
  path                                 The path to the access log to be parsed

Options:
      --log-type[=LOG-TYPE]            Specify whether it is a common or combined formatted Apache log. [default: "combined"]
      --ignore-agent[=IGNORE-AGENT]    Exclude all traffic that contains the Agent String you provide. [default: false]
      --ignore-bots                    Exclude all traffic from bots and spiders.
      --only-bots                      Only display traffic from bots and spiders.
      --ignore-files                   Exclude requests for static resources such as css, js, jpg files.
      --only-files                     Only show requests for static resources such as css, js, jpg files.
      --response-code[=RESPONSE-CODE]  Only show traffic based on a HTTP response code.
      --successful                     Only show 200 responses
      --redirection                    Only show 30x responses
      --not-found                      Only show 404 responses
      --client-errors                  Only show 40x responses
      --server-errors                  Only show 50x responses
      --unusual-agents                 Only show unusual agent traffic
      --today                          Only show today's data in the output.
      --current-month                  Only show this month's data in the output.
      --current-year                   Only show this year's data in the output.
      --this-agent[=THIS-AGENT]        Display only traffic from a specific user agent using a case-insensitive search term. [default: false]
      --this-referrer[=THIS-REFERRER]  Display only traffic from a specific referrer using a case-insensitive search term. [default: false]
      --this-uri[=THIS-URI]            Display only traffic to a specific URI using a case-insensitive search term. [default: false]
      --this-ip[=THIS-IP]              Display only traffic from a specific IP address [default: false]
  -h, --help                           Display this help message
  -q, --quiet                          Do not output any message
  -V, --version                        Display this application version
      --ansi                           Force ANSI output
      --no-ansi                        Disable ANSI output
  -n, --no-interaction                 Do not ask any interactive question
  -v|vv|vvv, --verbose                 Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Will return all lines from Apache log that matches a given query
```

## uri-origin Command

```
Description:
  Display a list of all referrer traffic to a specified origin.

Usage:
  uri-origin [options] [--] <path> <uri>

Arguments:
  path                                     The path to the access log to be parsed
  uri                                      Case insensitive term or full uri.

Options:
      --log-type[=LOG-TYPE]                Specify whether it is a common or combined formatted Apache log. [default: "combined"]
      --ignore-referrer[=IGNORE-REFERRER]  Ignore all referrers that match a given case insensitive uri. [default: false]
  -h, --help                               Display this help message
  -q, --quiet                              Do not output any message
  -V, --version                            Display this application version
      --ansi                               Force ANSI output
      --no-ansi                            Disable ANSI output
  -n, --no-interaction                     Do not ask any interactive question
  -v|vv|vvv, --verbose                     Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Will return a list of all referrer traffic to a case-insensitive uri search term.
```

### Prerequisites

- PHP 7.1.3 or greater
- Composer

```
You can download and learn more about composer by visiting https://getcomposer.org/
```

### Installing

Since this is a very light-weight set of command-line tools, installing Feather is a breeze. Make sure your server meets the minimum PHP requirements for the symfony/console component i.e. >7.1.3, before proceeding.

Clone the repo to your webserver or local machine by either downloading and extracting the zip file from Github or by using the following command:

```
git clone zenperfect/feather ./feather
```

Install dependencies using composer by navigating to the ethereal project folder and running:

```
composer install
```


## Deployment

Feather requires you to be running as root or via the sudo command **OR** as a user with read access to the log you are parsing. **Nothing will be removed from the filesystem in ANY operation!**

## Built With

* [Symfony Console Command](https://symfony.com/doc/current/components/console.html) - PHP Command Console Component

## Versioning

I use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Travis Coats** - *Initial work* - [Zen Perfect Design](https://www.zenperfectdesign.com)

## License

See the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Big thanks to the Symfony team for their awesome components!
* I hope these tools bring insight and useful results!