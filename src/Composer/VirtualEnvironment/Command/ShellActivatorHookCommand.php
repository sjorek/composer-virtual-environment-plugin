<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command;

use Composer\Composer;
use Composer\IO\IOInterface;
use Sjorek\Composer\VirtualEnvironment\Command\Config\CommandConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorHookConfiguration;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivatorHookCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $this
            ->setName('virtual-environment:shell-hook')
            ->setAliases(array('venv:shell-hook'))
            ->setDescription('Add or remove virtual environment shell activation hook scripts.')
            ->setDefinition(
                $this->addDefaultDefinition(
                    array(
                        new InputArgument(
                            'hook',
                            InputOption::VALUE_OPTIONAL,
                            'List of the shell activation script hooks.'
                        ),
                        new InputOption(
                            'name',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'The name of the shell activation script hook.'
                        ),
                        new InputOption(
                            'priority',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'The priority of the shell activation script hook.'
                        ),
                        new InputOption(
                            'script',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'The shell activation hook script.'
                        ),
                        new InputOption(
                            'shell',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'The name of or path to the shell.',
                            '%SHELL%'
                        ),
                    )
                )
            )
            ->setHelp(
                <<<EOT
The <info>virtual-environment:shell-hook</info> command creates files
triggered when the virtual environment shell is activated or deactivated.

Examples:

Simple shell script using the shell detection

    <info>php composer.phar venv:shell-hook post-activate \
        --name=composer-run-script-xyz \
        --script='composer run-script xyz'</info>

    Hint: The deployed script will run in the detected shell only

Simple shell script using the default shebang "#!/bin/sh"

    <info>php composer.phar venv:shell-hook post-activate \
        --name=composer-run-script-xyz \
        --script='composer run-script xyz' \
        --shell=sh</info>

    Hint: The deployed script will run in all supported shells

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
        return new ShellActivatorHookConfiguration($input, $output, $composer, $io);
    }

    /**
     * {@inheritDoc}
     * @throws \RuntimeException
     * @see AbstractProcessorCommand::deploy()
     */
    protected function deploy(CommandConfigurationInterface $config, OutputInterface $output)
    {
        $hooks = $config->get('shell-hook-expanded');
        if (empty($hooks)) {
            $output->writeln(
                '<error>Skipping creation of shell activation hooks, none available.</error>'
            );
        } else {
            $baseDir = $config->get('base-dir');
            $shellHookDir = $config->get('shell-hook-dir-expanded');
            foreach ($hooks as $hook => $hookConfigs) {
                foreach ($hookConfigs as $hookConfig) {
                    $processor = new ShellActivationHookProcessor(
                        $hook,
                        $hookConfig['name'],
                        $hookConfig['priority'],
                        $hookConfig['shell'],
                        $hookConfig['script'],
                        $baseDir,
                        $shellHookDir
                    );
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
        if (empty($hooks)) {
            $output->writeln(
                '<error>Skipping removal of shell activation hooks, as none is available.</error>'
            );
        } else {
            $baseDir = $config->get('base-dir');
            $shellHookDir = $config->get('shell-hook-dir-expanded');
            foreach ($hooks as $hook => $hookConfigs) {
                foreach ($hookConfigs as $hookConfig) {
                    $processor = new ShellActivationHookProcessor(
                        $hook,
                        $hookConfig['name'],
                        $hookConfig['priority'],
                        $hookConfig['shell'],
                        $hookConfig['script'],
                        $baseDir,
                        $shellHookDir
                    );
                    $processor->rollback($output, $config->get('force'));
                }
            }
        }
    }
}
