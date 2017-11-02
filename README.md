# [“virtual-environment”](https://sjorek.github.io/composer-virtual-environment-plugin/) [composer-plugin](http://getcomposer.org)

A [composer](http://getcomposer.org)-plugin adding three commands to composer:

1. `venv:shell` - activate/deactivate the composer virtual environment in shell
2. `venv:hook` - add/remove shell hooks, triggered on activating or deactivating the virtual environment
3. `venv:link` - create symlinks to arbitrary locations
4. `venv:git-hook` - create git-hooks from various sources


## Installation

### Method 1: globally, so it is available in all packages

```bash
php composer.phar global require sjorek/composer-virtual-environment-plugin
```


### Method 2: as a package requirement

```bash
php composer.phar require --dev sjorek/composer-virtual-environment-plugin
```


## Documentation

### Shell Activation Command

```bash
$ php composer.phar help venv:shell
Usage:
  virtual-environment:shell [options] [--] [<shell>]...
  venv:shell

Arguments:
  shell                          List of shell activators to add or remove.

Options:
      --name=NAME                Name of the virtual environment. [default: "{$name}"]
      --colors                   Enable the color prompt per default. Works currently only for "bash".
      --no-colors                Disable the color prompt per default.
  -a, --add                      Add to existing configuration.
  -r, --remove                   Remove all configured items.
  -s, --save                     Save configuration.
  -l, --local                    Use local configuration file "./composer-venv.json".
  -g, --global                   Use global configuration file "~/.composer/composer-venv.json".
  -c, --config-file=CONFIG-FILE  Use given configuration file.
      --lock                     Lock configuration in "./composer-venv.lock".
  -f, --force                    Force overwriting existing git-hooks
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --profile                  Display timing and memory usage information
      --no-plugins               Whether to disable plugins.
  -d, --working-dir=WORKING-DIR  If specified, use the given directory as working directory.
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output,
                                 2 for more verbose output and 3 for debug

Help:
  The virtual-environment:shell command creates files
  to activate and deactivate the current bin directory in shell.
  
  Usage:
  
      php composer.phar venv:shell
  
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


### Shell Activation Hook Command

```bash
$ php composer.phar help venv:shell-hook
Usage:
  virtual-environment:shell-hook [options] [--] [<hook>]...
  venv:shell-hook

Arguments:
  hook                           List of shell-hooks to add or remove.

Options:
      --name=NAME                The name of the shell-hook.
      --priority=PRIORITY        The priority of the shell-hook.
      --shell=SHELL              The name of or path to the shell.
      --script=SCRIPT            Use the given script as shell-hook.
      --file=FILE                Use the content of the given file as shell-hook.
      --link=LINK                Install shell-hook by creating a symbolic link to the given file.
      --url=URL                  Download the shell-hook from the given url.
  -a, --add                      Add to existing configuration.
  -r, --remove                   Remove all configured items.
  -s, --save                     Save configuration.
  -l, --local                    Use local configuration file "./composer-venv.json".
  -g, --global                   Use global configuration file "~/.composer/composer-venv.json".
  -c, --config-file=CONFIG-FILE  Use given configuration file.
      --lock                     Lock configuration in "./composer-venv.lock".
  -f, --force                    Force overwriting existing git-hooks
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --profile                  Display timing and memory usage information
      --no-plugins               Whether to disable plugins.
  -d, --working-dir=WORKING-DIR  If specified, use the given directory as working directory.
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output,
                                 2 for more verbose output and 3 for debug

Help:
  The virtual-environment:shell-hook command manages
  shell-hooks residing in the .composer-venv/shell directory.
  
  Examples:
  
  Simple shell script running in the detected shell only
  
      php composer.phar venv:shell-hook post-activate \
          --script='composer run-script xyz'
  
  Simple shell script running in all shells
  
      php composer.phar venv:shell-hook post-activate \
          --script='composer run-script xyz' \
          --shell=sh
  
  Utilizing environment variable expansion
  
      php composer.phar venv:shell-hook post-activate \
          --script='echo "I am using a %SHELL%!"' \
          --shell='%SHELL%'
  
  Utilizing configuration value expansion
  
      php composer.phar venv:shell-hook post-activate \
          --script='{$bin-dir}/php -r \'require "{$vendor-dir}/autoload.php"; Namespace\\Classname::staticMethod();\''
  
  Import file from relative path
  
      php composer.phar venv:shell-hook post-activate \
          --file=relative/path/to/post-activate.hook
  
  Import file from absolute path
  
      php composer.phar venv:shell-hook post-activate \
          --file=/absolute/path/to/post-activate.hook
  
  Create symlink to file
  
      php composer.phar venv:shell-hook post-activate \
          --link=../../path/to/post-activate.hook
  
  Relative hook file URL
  
      php composer.phar venv:shell-hook post-activate \
          --url=file://relative/path/to/post-activate.hook
  
  Absolute hook file URL
  
      php composer.phar venv:shell-hook post-activate \
          --url=file:///absolute/path/to/post-activate.hook
  
  Download hook file from an URL
  
      php composer.phar venv:shell-hook post-activate \
          --url=https://some.host/post-activate.hook
  
  Using a built-in hook file URL
  
      php composer.phar venv:shell-hook post-activate \
          --url=vfs://venv/shell-hook/post-activate.hook
  
```


### Symbolic Link Command

```bash
$ php composer.phar help venv:link
Usage:
  virtual-environment:link [options] [--] [<link>]...
  venv:link

Arguments:
  link                           List of symbolic links to add or remove.

Options:
  -a, --add                      Add to existing configuration.
  -r, --remove                   Remove all configured items.
  -s, --save                     Save configuration.
  -l, --local                    Use local configuration file "./composer-venv.json".
  -g, --global                   Use global configuration file "~/.composer/composer-venv.json".
  -c, --config-file=CONFIG-FILE  Use given configuration file.
      --lock                     Lock configuration in "./composer-venv.lock".
  -f, --force                    Force overwriting existing git-hooks
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --profile                  Display timing and memory usage information
      --no-plugins               Whether to disable plugins.
  -d, --working-dir=WORKING-DIR  If specified, use the given directory as working directory.
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output,
                                 2 for more verbose output and 3 for debug

Help:
  The virtual-environment:link command places symlinks
  to php- and composer-binaries in the bin directory.
  
  Example:
  
      php composer.phar venv:link '{$bin-dir}/composer':'{$bin-dir-up}/composer.phar'
  
  After this you can use the linked binaries in composer
  run-script or in virtual-environment:shell.
  
  Attention: only link the composer like in the example above,
  if your project does not require the composer/composer package.
  
```


### Git-Hook Command

```bash
$ php composer.phar help venv:git-hook 
Usage:
  virtual-environment:git-hook [options] [--] [<hook>]...
  venv:git-hook

Arguments:
  hook                           List of git-hooks to add or remove.

Options:
      --script=SCRIPT            Use the given script as git-hook.
      --shebang=SHEBANG          Use the given #!shebang for the given script.
      --file=FILE                Use the content of the given file as git-hook.
      --link=LINK                Install git-hook by creating a symbolic link to the given file.
      --url=URL                  Download the git-hook from the given url.
  -a, --add                      Add to existing configuration.
  -r, --remove                   Remove all configured items.
  -s, --save                     Save configuration.
  -l, --local                    Use local configuration file "./composer-venv.json".
  -g, --global                   Use global configuration file "~/.composer/composer-venv.json".
  -c, --config-file=CONFIG-FILE  Use given configuration file.
      --lock                     Lock configuration in "./composer-venv.lock".
  -f, --force                    Force overwriting existing git-hooks
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --profile                  Display timing and memory usage information
      --no-plugins               Whether to disable plugins.
  -d, --working-dir=WORKING-DIR  If specified, use the given directory as working directory.
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output,
                                 2 for more verbose output and 3 for debug

Help:
  The virtual-environment:git-hook command manages
  git-hooks residing in the .git/hooks directory.
  
  Examples:
  
  Simple shell script using default shebang "#!/bin/sh"
  
      php composer.phar venv:git-hook pre-commit \
          --script='composer run-script xyz'
  
  Shell script with a more complex shebang
  
      php composer.phar venv:git-hook pre-commit \
          --shebang='/usr/bin/env bash' \
          --script='echo "about to commit"'
  
  Simple PHP script

      # notice the detection of the correct shebang
      php composer.phar venv:git-hook pre-commit \
          --script='<?php echo "about to commit";'
  
  Utilizing environment variable expansion
  
      php composer.phar venv:git-hook pre-commit \
          --shebang=%SHELL% \
          --script='echo "I am using a %SHELL%!"'
  
  Utilizing configuration value expansion
  
      php composer.phar venv:git-hook pre-commit \
          --shebang='{$bin-dir}/php' \
          --script='<?php
                  require "{$vendor-dir}/autoload.php";
                  Namespace\Classname::staticMethod();'
  
  Import file from relative path
  
      php composer.phar venv:git-hook pre-commit \
          --file=relative/path/to/pre-commit.hook
  
  Import file from absolute path
  
      php composer.phar venv:git-hook pre-commit \
          --file=/absolute/path/to/pre-commit.hook
  
  Create symlink to file
  
      php composer.phar venv:git-hook pre-commit \
          --link=../../path/to/pre-commit.hook
  
  Relative hook file URL
  
      php composer.phar venv:git-hook pre-commit \
          --url=file://relative/path/to/pre-commit.hook
  
  Absolute hook file URL
  
      php composer.phar venv:git-hook pre-commit \
          --url=file:///absolute/path/to/pre-commit.hook
  
  Download hook file from an URL
  
      php composer.phar venv:git-hook pre-commit \
          --url=https://some.host/pre-commit.hook
  
  Using a built-in hook file URL
  
      php composer.phar venv:git-hook pre-commit \
          --url=vfs://venv/git-hook/pre-commit.hook
  
```


## Contributing

Look at the [contribution guidelines](CONTRIBUTING.md)


## Want more?

There is a [bash-completion implementation](https://sjorek.github.io/composer-bash-completion/)
complementing this composer-plugin. And if you're using [MacPorts](http://macports.org),
especially if you're using my [MacPorts-PHP](https://sjorek.github.io/MacPorts-PHP/)
repository, everything should work like a breeze.

## Links

### Status

[![Build Status](https://img.shields.io/travis/sjorek/composer-virtual-environment-plugin.svg)](https://travis-ci.org/sjorek/composer-virtual-environment-plugin)
[![Dependency Status](https://img.shields.io/gemnasium/sjorek/composer-virtual-environment-plugin.svg)](https://gemnasium.com/github.com/sjorek/composer-virtual-environment-plugin)


### GitHub

[![GitHub Issues](https://img.shields.io/github/issues/sjorek/composer-virtual-environment-plugin.svg)](https://github.com/sjorek/composer-virtual-environment-plugin/issues)
[![GitHub Latest Tag](https://img.shields.io/github/tag/sjorek/composer-virtual-environment-plugin.svg)](https://github.com/sjorek/composer-virtual-environment-plugin/tags)
[![GitHub Total Downloads](https://img.shields.io/github/downloads/sjorek/composer-virtual-environment-plugin/total.svg)](https://github.com/sjorek/composer-virtual-environment-plugin/releases)


### Packagist

[![Packagist Latest Stable Version](https://poser.pugx.org/sjorek/composer-virtual-environment-plugin/version)](https://packagist.org/packages/sjorek/composer-virtual-environment-plugin)
[![Packagist Total Downloads](https://poser.pugx.org/sjorek/composer-virtual-environment-plugin/downloads)](https://packagist.org/packages/sjorek/composer-virtual-environment-plugin)
[![Packagist Latest Unstable Version](https://poser.pugx.org/sjorek/composer-virtual-environment-plugin/v/unstable)](https:////packagist.org/packages/sjorek/composer-virtual-environment-plugin)
[![Packagist License](https://poser.pugx.org/sjorek/composer-virtual-environment-plugin/license)](https://packagist.org/packages/sjorek/composer-virtual-environment-plugin)


### Social

[![GitHub Forks](https://img.shields.io/github/forks/sjorek/composer-virtual-environment-plugin.svg?style=social)](https://github.com/sjorek/composer-virtual-environment-plugin/network)
[![GitHub Stars](https://img.shields.io/github/stars/sjorek/composer-virtual-environment-plugin.svg?style=social)](https://github.com/sjorek/composer-virtual-environment-plugin/stargazers)
[![GitHub Watchers](https://img.shields.io/github/watchers/sjorek/composer-virtual-environment-plugin.svg?style=social)](https://github.com/sjorek/composer-virtual-environment-plugin/watchers)
[![Twitter](https://img.shields.io/twitter/url/https/github.com/sjorek/composer-virtual-environment-plugin.svg?style=social)](https://twitter.com/intent/tweet?url=https%3A%2F%2Fsjorek.github.io%2Fcomposer-virtual-environment-plugin%2F)

