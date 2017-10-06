# “virtual-environment” command-plugin for composer

[![Dependency Status](https://gemnasium.com/badges/github.com/sjorek/composer-virtual-environment-plugin.svg)](https://gemnasium.com/github.com/sjorek/composer-virtual-environment-plugin)

A composer-plugin adding a command to activate/deactivate the current
bin-directory in shell, optionally creating symlinks to the composer-
and php-binary in the bin-directory.

## Installation

```bash
php composer.phar require-dev sjorek/composer-virtual-environment-plugin
```

## Usage

```bash
# initial setup example...
/opt/local/bin/php70 /opt/local/lib/php70/composer.phar virtual-environment --php=/opt/local/bin/php70 --update-local

# after this you can always ...
source vendor/bin/activate # if you're using bash, for other shells see [Documentation].
# which adds vendor/bin to you're PATH

# now use any binary from vendor/bin, like ...
php-cs-fixer fix
# or even ...
composer help # <-- notice that we don't need to specify path to php explictly

# if you're done, issue ...
deactivate
# and vendor/bin will be removed from your PATH

```

## Documentation

```console
$ php composer.phar help virtual-environment
[33mUsage:[39m
  virtual-environment [options]
  virtualenvironment
  venv

[33mOptions:[39m
  [32m    --name=NAME[39m                Name of the virtual environment.[33m [default: "vendor/package-name"][39m
  [32m    --shell=SHELL[39m              Set the list of shell activators to deploy.[33m (multiple values allowed)[39m
  [32m    --php=PHP[39m                  Add symlink to php.
  [32m    --composer=COMPOSER[39m        Add symlink to composer.[33m [default: "composer.phar"][39m
  [32m    --update-local[39m             Update the local virtual environment configuration recipe in "./composer.venv".
  [32m    --update-global[39m            Update the global virtual environment configuration recipe in "~/.composer/composer.venv".
  [32m    --ignore-local[39m             Ignore the local virtual environment configuration recipe in "./composer.venv".
  [32m    --ignore-global[39m            Ignore the global virtual environment configuration recipe in "~/.composer/composer.venv".
  [32m    --remove[39m                   Remove any deployed shell activators or symbolic links.
  [32m-f, --force[39m                    Force overwriting existing environment scripts
  [32m-h, --help[39m                     Display this help message
  [32m-q, --quiet[39m                    Do not output any message
  [32m-V, --version[39m                  Display this application version
  [32m    --ansi[39m                     Force ANSI output
  [32m    --no-ansi[39m                  Disable ANSI output
  [32m-n, --no-interaction[39m           Do not ask any interactive question
  [32m    --profile[39m                  Display timing and memory usage information
  [32m    --no-plugins[39m               Whether to disable plugins.
  [32m-d, --working-dir=WORKING-DIR[39m  If specified, use the given directory as working directory.
  [32m-v|vv|vvv, --verbose[39m           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

[33mHelp:[39m
  The [32mvirtual-environment[39m command creates files to activate
  and deactivate the current bin directory in shell,
  optionally placing symlinks to php- and composer-binaries
  in the bin directory.
  
  Usage:
  
      [32mphp composer.phar virtual-environment[39m
  
  After this you can source the activation-script
  corresponding to your shell.
  
  if only one shell-activator or bash and zsh have been deployed:
      [32msource vendor/bin/activate[39m
  
  csh:
      [32msource vendor/bin/activate.csh[39m
  
  fish:
      [32m. vendor/bin/activate.fish[39m
  
  bash (alternative):
      [32msource vendor/bin/activate.bash[39m
  
  zsh (alternative):
      [32msource vendor/bin/activate.zsh[39m
  
```

## Want more?

There is a [bash-completion implementation](https://sjorek.github.io/composer-bash-completion/)
complementing this composer-plugin. And if you're using [MacPorts](http://macports.org),
especially if you're using my [MacPorts-PHP](https://sjorek.github.io/MacPorts-PHP/)
repository, everything should work like a breeze.

Cheers!
