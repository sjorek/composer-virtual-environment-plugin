<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command;

use Composer\Composer;
use Composer\IO\IOInterface;
use Sjorek\Composer\VirtualEnvironment\Command\Config\ShellHookConfiguration;
use Sjorek\Composer\VirtualEnvironment\Command\Config\CommandConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellHook;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\ShellHookProcessorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellHookCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $shellHookDir = ShellHookProcessorInterface::SHELL_HOOK_DIR;

        $this
            ->setName('virtual-environment:shell-hook')
            ->setAliases(array('venv:shell-hook'))
            ->setDescription('Add or remove virtual environment shell-hooks.')
            ->setDefinition(
                $this->addDefaultDefinition(
                    array(
                        new InputArgument(
                            'hook',
                            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                            'List of shell-hooks to add or remove.'
                        ),
                        new InputOption(
                            'name',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'The name of the shell-hook.'
                        ),
                        new InputOption(
                            'priority',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'The priority of the shell-hook.'
                        ),
                        new InputOption(
                            'shell',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'The name of or path to the shell.'
                        ),
                        new InputOption(
                            'script',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Use the given script as shell-hook.'
                        ),
                        new InputOption(
                            'file',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Use the content of the given file as shell-hook.'
                        ),
                        new InputOption(
                            'link',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Install shell-hook by creating a symbolic link to the given file.'
                        ),
                        new InputOption(
                            'url',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Download the shell-hook from the given url.'
                        ),
                    )
                )
            )
            ->setHelp(
                <<<EOT
The <info>virtual-environment:shell-hook</info> command manages
shell-hooks residing in the <info>${shellHookDir}</info> directory.

Examples:

Simple shell script running in the detected shell only

    <info>php composer.phar venv:shell-hook post-activate \
        --script='composer run-script xyz'</info>

Simple shell script running in all shells

    <info>php composer.phar venv:shell-hook post-activate \
        --script='composer run-script xyz' \
        --shell=sh</info>

Utilizing environment variable expansion

    <info>php composer.phar venv:shell-hook post-activate \
        --script='echo "I am using a %SHELL%!"' \
        --shell='%SHELL%'</info>

Utilizing configuration value expansion

    <info>php composer.phar venv:shell-hook post-activate \
        --script='{\$bin-dir}/php -r \\'require "{\$vendor-dir}/autoload.php"; Namespace\\\\Classname::staticMethod();\\''</info>

Import file from relative path

    <info>php composer.phar venv:shell-hook post-activate \
        --file=relative/path/to/post-activate.hook</info>

Import file from absolute path

    <info>php composer.phar venv:shell-hook post-activate \
        --file=/absolute/path/to/post-activate.hook</info>

Create symlink to file

    <info>php composer.phar venv:shell-hook post-activate \
        --link=../../path/to/post-activate.hook</info>

Relative hook file URL

    <info>php composer.phar venv:shell-hook post-activate \
        --url=file://relative/path/to/post-activate.hook</info>

Absolute hook file URL

    <info>php composer.phar venv:shell-hook post-activate \
        --url=file:///absolute/path/to/post-activate.hook</info>

Download hook file from an URL

    <info>php composer.phar venv:shell-hook post-activate \
        --url=https://some.host/post-activate.hook</info>

Using a built-in hook file URL

    <info>php composer.phar venv:shell-hook post-activate \
        --url=vfs://venv/shell-hook/post-activate.hook</info>

EOT
            );
    }

    /**
     * {@inheritDoc}
     * @see AbstractProcessorCommand::getCommandConfiguration()
     */
    protected function getCommandConfiguration(
        InputInterface $input,
        OutputInterface $output,
        Composer $composer,
        IOInterface $io
    ) {
        return new ShellHookConfiguration($input, $output, $composer, $io);
    }

    /**
     * {@inheritDoc}
     * @see AbstractProcessorCommand::deploy()
     */
    protected function deploy(CommandConfigurationInterface $config, OutputInterface $output)
    {
        $hooks = $config->get('shell-hook-expanded');
        if (empty(array_filter($hooks))) {
            $output->writeln(
                '<error>Skipping creation of shell-hooks, as none is available.</error>'
            );
        } else {
            $baseDir = $config->get('base-dir', '');
            $shellHookDir = $config->get('shell-hook-dir', null);
            foreach ($hooks as $hook => $hookConfigs) {
                foreach ($hookConfigs as $name => $hookConfig) {
                    $processor = $this->getShellHookProcessor($hook, $name, $hookConfig, $baseDir, $shellHookDir);
                    if ($processor === null) {
                        $output->writeln(
                            sprintf(
                                '<error>Missing or invalid shell-hook type for hook %s.</error>',
                                $hook
                            ),
                            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                        );
                        continue;
                    }
                    $processor->deploy($output, $config->get('force'));
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see AbstractProcessorCommand::rollback()
     */
    protected function rollback(CommandConfigurationInterface $config, OutputInterface $output)
    {
        $hooks = $config->get('shell-hook-expanded');
        if (empty(array_filter($hooks))) {
            $output->writeln(
                '<error>Skipping removal of shell-hooks, as none is available.</error>'
            );
        } else {
            $baseDir = $config->get('base-dir', '');
            $shellHookDir = $config->get('shell-hook-dir', null);
            foreach ($hooks as $hook => $hookConfigs) {
                foreach ($hookConfigs as $name => $hookConfig) {
                    $processor = $this->getShellHookProcessor($hook, $name, $hookConfig, $baseDir, $shellHookDir);
                    if ($processor === null) {
                        $output->writeln(
                            sprintf(
                                '<error>Missing or invalid shell-hook type for hook %s.</error>',
                                $hook
                                ),
                            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                            );
                        continue;
                    }
                    $processor->rollback($output, $config->get('force'));
                }
            }
        }
    }

    /**
     * @param  string                           $hook
     * @param  string                           $name
     * @param  array                            $config
     * @param  string                           $baseDir
     * @param  string|null                      $shellHookDir
     * @return ShellHookProcessorInterface|null
     */
    protected function getShellHookProcessor($hook, $name, array $config, $baseDir, $shellHookDir)
    {
        $shell = $config['shell'];
        if (isset($config['script'])) {
            return new ShellHook\ScriptProcessor($hook, $name, $shell, $config['script'], $baseDir, $shellHookDir);
        } elseif (isset($config['file'])) {
            return new ShellHook\FileProcessor($hook, $name, $shell, $config['file'], $baseDir, $shellHookDir);
        } elseif (isset($config['link'])) {
            return new ShellHook\SymbolicLinkProcessor($hook, $name, $shell, $config['link'], $baseDir, $shellHookDir);
        } elseif (isset($config['url'])) {
            return new ShellHook\StreamProcessor($hook, $name, $shell, $config['url'], $baseDir, $shellHookDir);
        } else {
            return null;
        }
    }
}
