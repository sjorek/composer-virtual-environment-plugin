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
use Sjorek\Composer\VirtualEnvironment\Command\Config\GitHookConfiguration;
use Sjorek\Composer\VirtualEnvironment\Command\Config\ConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class GitHookCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $config = $this->getComposer()->getConfig();
        $home = $config->get('home');

        $this
            ->setName('virtual-environment:git-hook')
            ->setAliases(array('venv:git-hook'))
            ->setDescription('Add or remove virtual environment git hooks.')
            ->setDefinition(array(
                new InputArgument('hook', InputOption::VALUE_OPTIONAL, 'List of git-hooks to add or remove.'),
                new InputOption('manifest', 'm', InputOption::VALUE_NONE, 'Use package manifest file "./composer.json". [default]'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Use given configuration file.'),
                new InputOption('local', 'l', InputOption::VALUE_NONE, 'Use local configuration file "./composer.venv".'),
                new InputOption('global', 'g', InputOption::VALUE_NONE, 'Use global configuration file "' . $home .'/composer.venv".'),
                new InputOption('save', 's', InputOption::VALUE_NONE, 'Save configuration file.'),
                new InputOption('remove', 'r', InputOption::VALUE_NONE, 'Remove any deployed git-hooks.'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force overwriting existing git-hooks'),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment:git-hook</info> command places 
git-hook in the .git directory.

Example:

    <info>php composer.phar venv:git-hook pre-commit:'echo about to commit'</info>

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
        return new GitHookConfiguration($input, $output, $composer, $io);
    }

    protected function deploy(ConfigurationInterface $config, OutputInterface $output)
    {
        $hooks = $config->get('git-hook-expanded');
        if (empty($hooks)) {
            $output->writeln(
                '<comment>Skipping creation of git-hooks, as none is available.</comment>',
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );
        } else {
            $baseDir = $config->get('base-dir', '');
            foreach ($hooks as $source => $target) {
                $processor = new Processor\GitHookProcessor($source, $target, $baseDir);
                $processor->deploy($output, $config->get('force'));
            }
        }
        $config->save($config->get('force'));
    }

    protected function rollback(ConfigurationInterface $config, OutputInterface $output)
    {
        $hooks = $config->get('git-hook-expanded');
        if (empty($hooks)) {
            $output->writeln(
                '<comment>Skipping removal of git-hooks, as none is available.</comment>',
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );
        } else {
            $baseDir = $config->get('base-dir', '');
            foreach ($hooks as $source => $target) {
                $processor = new Processor\GitHookProcessor($source, $target, $baseDir);
                $processor->rollback($output);
            }
        }
    }
}
