# “virtual-environment” command-plugin for composer

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
composer virtual-environment --php=/opt/local/bin/php70 --update-local

# after this you can always ...
source vendor/bin/activate # if you're using bash, for other shells see [Documentation].
# which adds vendor/bin to you're PATH

# now use any binary from vendor/bin, like ...
php-cs-fixer

# if you're done, issue ...
deactivate
# and vendor/bin will be removed from your PATH

```

## Documentation

    php composer.phar help virtual-environment
    Usage:
      virtual-environment [options]
    
    Options:
          --name=NAME                Name of the virtual environment. [default: "vendor/package-name"]
          --shell=SHELL              Set the list of shell activators to deploy. [default: ["bash","csh","fish","zsh"]] (multiple values allowed)
          --php=PHP                  Add symlink to php.
          --composer=COMPOSER        Add symlink to composer. [default: "composer.phar"]
          --update-local             Update the local virtual environment configuration recipe in "./composer.venv".
          --update-global            Update the global virtual environment configuration recipe in "~/.composer/composer.venv".
          --ignore-local             Ignore the local virtual environment configuration recipe in "./composer.venv".
          --ignore-global            Ignore the global virtual environment configuration recipe in "~/.composer/composer.venv".
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
      optionally placing a symlinks to php- and composer-binaries
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

