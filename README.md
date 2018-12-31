# Feather Toolkit

Feather is a lightweight and intuitive CLI for parsing Apache logs and pulling out useful statistics about your web traffic. It also offers simple reporting options to allow for record-keeping for historical insight.. The intent of this toolset is to provide useful tools that are easily expandable and applicable to the majority of web hosting providers out there. All you need is PHP 7.1.3 or greater and composer and you are on your way to a powerful command-line pipeline.

## Getting Started

I recommend cloning a copy to your local machine first to get used to the commands. Since this is my first Github project, I will do my best to document everything as much as possible and I apologize ahead of time for my Github ignorance!


**List Commands**: 

```
php feather
```

This will provide a detailed list of all available commands a quick description of what they do. **Everything in Feather is non-destructive**. When gathering information from your server that generates additional or transformed information, that transformed information is stored in the app folder.

**Getting Command Help**:

```
php feather access-stats --help
```

This will list all of the arguments and options available when performing the access-stats command. Most of the options available stack on each other and help you further refine a search *e.g.*:

```
php feather access-stats /path/to/ssl.access_log -BRT -A Fedora --this-agent="python"
```

The above command will parse your ssl.access_log and then return all lines that are:
 
 - **No** BOTS (B)
 - **No** RESOURCEs i.e. files such as .js, .css, etc (R), 
 - TODAY (T) traffic only
 - **No** AGENT (A) traffic with Fedora in the string
 - --this-agent Show only traffic where the agent contains the word python (case-insensitive)
 
You could optionally tag on a (-z) to save as a filed report and/or output it to JSON using (-j) to use as an application endpoint. Making Feather an awesome solution to feed raw Apache data into your web app! 

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

End with an example of getting some data out of the system or using it for a little demo


## Deployment

Feather requires you to be running as root or via the sudo command **OR** as a user with read access to the log you are parsing. **Nothing will be removed from the filesystem in ANY operation!**

## Built With

* [Symfony Console Command](https://symfony.com/doc/current/components/console.html) - PHP Command Console Component
* [Monolog](https://github.com/Seldaek/monolog) - Awesome PHP Logging

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/PurpleBooth/b24679402957c63ec426) for details on the Zen Perfect Design code of conduct, and the process for submitting pull requests to me.

## Versioning

I use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Travis Coats** - *Initial work* - [Zen Perfect Design](https://www.zenperfectdesign.com)

See also the list of [contributors](https://github.com/your/project/contributors) who participated in this project.

## License

See the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Big thanks to the Symfony team for their awesome components!
* I hope these tools bring insight and useful results!