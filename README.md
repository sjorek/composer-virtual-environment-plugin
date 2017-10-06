# “virtual-environment” command-plugin for composer

[![Dependency Status](https://gemnasium.com/badges/github.com/sjorek/composer-virtual-environment-plugin.svg)](https://gemnasium.com/github.com/sjorek/composer-virtual-environment-plugin)

A composer-plugin adding a command to activate/deactivate the current
bin-directory in shell, optionally creating symlinks to the composer-
and php-binary in the bin-directory.


## Installation

```bash
php composer.phar require-dev sjorek/composer-virtual-environment-plugin
```


## Documentation

```console
$ php composer.phar help virtual-environment
Usage:
  virtual-environment [options]
  virtualenvironment
  venv

Options:
      --name=NAME                Name of the virtual environment. [default: "vendor/package-name"]
      --shell=SHELL              Set the list of shell activators to deploy. (multiple values allowed)
      --php=PHP                  Add symlink to php.
      --composer=COMPOSER        Add symlink to composer. [default: "composer.phar"]
      --update-local             Update the local virtual environment configuration recipe in "./composer.venv".
      --update-global            Update the global virtual environment configuration recipe in "~/.composer/composer.venv".
      --ignore-local             Ignore the local virtual environment configuration recipe in "./composer.venv".
      --ignore-global            Ignore the global virtual environment configuration recipe in "~/.composer/composer.venv".
      --remove                   Remove any deployed shell activators or symbolic links.
  -f, --force                    Force overwriting existing environment scripts
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --profile                  Display timing and memory usage information
      --no-plugins               Whether to disable plugins.
  -d, --working-dir=WORKING-DIR  If specified, use the given directory as working directory.
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The virtual-environment command creates files to activate
  and deactivate the current bin directory in shell,
  optionally placing symlinks to php- and composer-binaries
  in the bin directory.
  
  Usage:
  
      php composer.phar virtual-environment
  
  After this you can source the activation-script
  corresponding to your shell.
  
  if only one shell-activator or bash and zsh have been deployed:
      source vendor/bin/activate
  
  csh:
      source vendor/bin/activate.csh
  
  fish:
      . vendor/bin/activate.fish
  
  bash (alternative):
      source vendor/bin/activate.bash
  
  zsh (alternative):
      source vendor/bin/activate.zsh
  
```


## Usage Scenarios

### Example: multiple PHP versions for many composer packages

Assuming the following:

* you're developing a composer package:
  * name: `vendor/first-example-package`
  * requires `php` version 7.0
* You're developing another composer package:
  * name: `vendor/second-example-package`
  * requires `php` version 7.2
* you want to use the `composer-virtual-environment-plugin` in all of
  your packages (in this case the two mentioned above) *without
  cluttering the packages with files* and *without adding the plugin
  to the package requirements*
* in this example you're using `bash` as your favorite shell
* you have `php` version 7.0 installed in `/path/to/bin/php70`
* you have `php` version 7.2 installed in `/path/to/bin/php72`
* you already installed/downloaded `composer.phar` somewhere in your
  filesystem, let's say under `/path/to/composer.phar`

```console
$ # install the plugin >>>globally<<< (in this case ${HOME}/.composer):
$ /path/to/bin/php70 /path/to/composer.phar global require sjorek/composer-virtual-environment-plugin
Changed current directory to /Users/sjorek/.composer
Using version ^X.Y.Z for sjorek/composer-virtual-environment-plugin
./composer.json has been updated
Loading composer repositories with package information
Updating dependencies (including require-dev)
Package operations: 1 install, 0 updates, 0 removals
  - Installing sjorek/composer-virtual-environment-plugin (X.Y.Z)
Writing lock file
Generating autoload files
```


#### vendor/first-example-package

```console
$ # change directory to your first package:
$ cd /path/to/vendor/first-example-package

$ # initial setup of the virtual composer shell environment (run this only once per package):
$ /path/to/bin/php70 /path/to/composer.phar venv --php=/path/to/bin/php70 --shell=bash
Installed virtual environment activation script: /path/to/vendor/first-example-package/vendor/bin/activate.bash
Installed virtual environment symlink: /path/to/vendor/first-example-package/vendor/bin/activate -> activate.bash
Installed virtual environment symlink: /path/to/vendor/first-example-package/vendor/bin/composer -> /path/to/composer.phar
Installed virtual environment symlink: /path/to/vendor/first-example-package/vendor/bin/php -> /path/to/bin/php70

$ # after this you can activate the virtual composer shell environment:
$ source vendor/bin/activate

virtual composer shell environment

    Name: vendor/first-example-package
    Path: /path/to/vendor/first-example-package

Run 'deactivate' to exit the environment and return to normal shell.

$ # the directory '/path/to/vendor/first-example-package/vendor/bin' is 
$ # now prepended to your PATH environment variable

$ # now use any binary from package requirements located in 'vendor/bin':
(vendor/first-example-package) $ php-cs-fixer fix # <-- just an example!

...

$ # you can use `composer` without specifying the path to php anymore:
(vendor/first-example-package) $ composer --version
Composer version 1.5.2 2017-09-11 16:59:25

$ # and of course you can now also use `php` directly:
(vendor/first-example-package) $ php --version
PHP 7.0.24 (cli) (built: Sep 29 2017 00:27:16) ( NTS )
Copyright (c) 1997-2017 The PHP Group
Zend Engine v3.0.0, Copyright (c) 1998-2017 Zend Technologies
    with Zend OPcache v7.0.24, Copyright (c) 1999-2017, by Zend Technologies
    with Xdebug v2.5.5, Copyright (c) 2002-2017, by Derick Rethans

$ # if you're done, run ...
$ deactivate

Left virtual composer shell environment.

Good Bye!

$ # ... and vendor/bin will be removed from your PATH
```


#### vendor/second-example-package

```console
$ # now change directory to your second package:
$ cd /path/to/vendor/second-example-package

$ # initial setup of the virtual composer shell environment (run this only once per package):
$ /path/to/bin/php72 /path/to/composer.phar venv --php=/path/to/bin/php72 --shell=bash
Installed virtual environment activation script: /path/to/vendor/second-example-package/vendor/bin/activate.bash
Installed virtual environment symlink: /path/to/vendor/second-example-package/vendor/bin/activate -> activate.bash
Installed virtual environment symlink: /path/to/vendor/second-example-package/vendor/bin/composer -> /path/to/composer.phar
Installed virtual environment symlink: /path/to/vendor/second-example-package/vendor/bin/php -> /path/to/bin/php70

...

$ # after this you can activate the virtual composer shell environment:
$ source vendor/bin/activate

virtual composer shell environment

    Name: vendor/second-example-package
    Path: /path/to/vendor/second-example-package

Run 'deactivate' to exit the environment and return to normal shell.

$ # the directory '/path/to/vendor/first-example-package/vendor/bin' is 
$ # now prepended to your PATH environment variable

$ # now use any binary from package requirements located in 'vendor/bin':
(vendor/second-example-package) $ composer --version # <-- notice that we don't need to specify path to php anymore
Composer version 1.5.2 2017-09-11 16:59:25

(vendor/second-example-package) $ php --version # <-- notice that we don't need to specify path to php anymore
PHP 7.2.0RC3 (cli) (built: Sep 28 2017 21:07:15) ( NTS )
Copyright (c) 1997-2017 The PHP Group
Zend Engine v3.2.0-dev, Copyright (c) 1998-2017 Zend Technologies
    with Zend OPcache v7.2.0RC3, Copyright (c) 1999-2017, by Zend Technologies

$ # if you're done, run ...
(vendor/second-example-package) $ deactivate

Left virtual composer shell environment.

Good Bye!

$ # ... and 'vendor/bin' will be removed from your PATH
```

## Want more?

There is a [bash-completion implementation](https://sjorek.github.io/composer-bash-completion/)
complementing this composer-plugin. And if you're using [MacPorts](http://macports.org),
especially if you're using my [MacPorts-PHP](https://sjorek.github.io/MacPorts-PHP/)
repository, everything should work like a breeze.

Cheers!
