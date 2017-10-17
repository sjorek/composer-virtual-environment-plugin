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
use Composer\Util\Platform;
use Sjorek\Composer\VirtualEnvironment\Command\Config\ConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorConfiguration;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivatorCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $home = $this->getComposer()->getConfig()->get('home');

        $this
            ->setName('virtual-environment:shell')
            ->setAliases(array('venv:shell'))
            ->setDescription('Setup or teardown virtual environment shell activation scripts.')
            ->setDefinition(array(
                new InputArgument('shell', InputOption::VALUE_OPTIONAL, 'List of shell activators to add or remove.'),
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the virtual environment.', '{$name}'),
                new InputOption('colors', null, InputOption::VALUE_NONE, 'Enable the color prompt per default. Works currently only for "bash".'),
                new InputOption('no-colors', null, InputOption::VALUE_NONE, 'Disable the color prompt per default.'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Use given configuration file.'),
                new InputOption('local', 'l', InputOption::VALUE_NONE, 'Use local configuration file "./composer.venv".'),
                new InputOption('global', 'g', InputOption::VALUE_NONE, 'Use global configuration file "' . $home .'/composer.venv".'),
                new InputOption('save', 's', InputOption::VALUE_NONE, 'Save configuration file.'),
                new InputOption('remove', 'r', InputOption::VALUE_NONE, 'Remove any deployed shell activation scripts.'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force overwriting existing environment files and links'),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment:shell-activator</info> command creates files
to activate and deactivate the current bin directory in shell.

Usage:

    <info>php composer.phar venv</info>

After this you can source the activation-script
corresponding to your shell.

if only one shell-activator or bash and zsh have been deployed:
    <info>source vendor/bin/activate</info>

csh:
    <info>source vendor/bin/activate.csh</info>

fish:
    <info>. vendor/bin/activate.fish</info>

bash (alternative):
    <info>source vendor/bin/activate.bash</info>

zsh (alternative):
    <info>source vendor/bin/activate.zsh</info>

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
        return new ShellActivatorConfiguration($input, $output, $composer, $io);
    }

    const BASH_TEMPLATE_COMMANDS = array(
        '@TPUT_COLORS@' => 'tput colors',
        '@TPUT_BOLD@' => 'tput bold',
        '@TPUT_SMUL@' => 'tput smul',
        '@TPUT_SMSO@' => 'tput smso',
        '@TPUT_SGR0@' => 'tput sgr0',
        '@TPUT_SETAF_0@' => 'tput setaf 0',
        '@TPUT_SETAF_1@' => 'tput setaf 1',
        '@TPUT_SETAF_2@' => 'tput setaf 2',
        '@TPUT_SETAF_3@' => 'tput setaf 3',
        '@TPUT_SETAF_4@' => 'tput setaf 4',
        '@TPUT_SETAF_5@' => 'tput setaf 5',
        '@TPUT_SETAF_6@' => 'tput setaf 6',
        '@TPUT_SETAF_7@' => 'tput setaf 7',
    );

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Command\AbstractProcessorCommand::deploy()
     */
    protected function deploy(ConfigurationInterface $config, OutputInterface $output)
    {
        $activators = $config->get('shell');
        if (empty($activators)) {
            $output->writeln(
                '<comment>Skipping creation of shell activators, none available.</comment>',
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );
        } else {
            $data = array(
                '@NAME@' => $config->get('name-expanded'),
                '@BASE_DIR@' => $config->get('base-dir'),
                '@BIN_DIR@' => $config->get('bin-dir'),
                '@COLORS@' => $config->get('colors') ? '1' : '0',
            );
            if (in_array('bash', $activators)) {
                $bash = 'bash';
                if (isset($_SERVER['SHELL']) && basename($_SERVER['SHELL']) === 'bash') {
                    $bash = $_SERVER['SHELL'];
                } elseif (isset($_ENV['SHELL']) && basename($_ENV['SHELL']) === 'bash') {
                    $bash = $_ENV['SHELL'];
                }
                // TODO check that $bash is really a bash? check version or issue a command only bash supports!
                foreach (self::BASH_TEMPLATE_COMMANDS as $key => $command) {
                    $data[$key] = exec(
                        sprintf(
                            '( echo %s | %s -ls ) 2>/dev/null',
                            escapeshellarg($command),
                            escapeshellcmd($bash)
                        )
                    );
                }
            }
            $baseDir = $config->get('base-dir');
            foreach (Processor\ActivationScriptProcessor::export($activators) as $filename) {
                $source = $config->get('resource-dir') . '/' . $filename;
                $target = $config->get('bin-dir-relative') . '/' . $filename;
                $processor = new Processor\ActivationScriptProcessor($source, $target, $baseDir, $data);
                $processor->deploy($output, $config->get('force'));
            }
            if ($config->has('link-expanded')) {
                $symlinks = $config->get('link-expanded');
                if (empty($symlinks)) {
                    $output->writeln(
                        '<comment>Skipping creation of symbolic link to shell activation script, as none is needed.</comment>',
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );
                } elseif (Platform::isWindows()) {
                    $output->writeln(
                        '<warning>Symbolic link to shell activation script is not (yet) supported on windows.</warning>'
                    );
                } else {
                    foreach ($symlinks as $source => $target) {
                        $processor = new Processor\SymbolicLinkProcessor($source, $target, $baseDir);
                        $processor->deploy($output, $config->get('force'));
                    }
                }
            }
        }

        $config->save($config->get('force'));
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Command\AbstractProcessorCommand::rollback()
     */
    protected function rollback(ConfigurationInterface $config, OutputInterface $output)
    {
        $activators = $config->get('shell');
        if (empty($activators)) {
            $output->writeln(
                '<comment>Skipping removal of shell activation scripts, as none is available.</comment>',
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );
        } else {
            $baseDir = $config->get('base-dir');
            foreach (Processor\ActivationScriptProcessor::export($activators) as $filename) {
                $source = $config->get('resource-dir') . '/' . $filename;
                $target = $config->get('bin-dir-relative') . '/' . $filename;
                $processor = new Processor\ActivationScriptProcessor($source, $target, $baseDir, array());
                $processor->rollback($output);
            }
            if ($config->has('link-expanded')) {
                $symlinks = $config->get('link-expanded');
                if (empty($symlinks)) {
                    $output->writeln(
                        '<comment>Skipping removal of symbolic link to shell activation script, as none is needed.</comment>',
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );
                } elseif (Platform::isWindows()) {
                    $output->writeln(
                        '<warning>Symbolic link to shell activation script is not (yet) supported on windows.</warning>'
                    );
                } else {
                    foreach ($symlinks as $source => $target) {
                        $processor = new Processor\SymbolicLinkProcessor($source, $target, $baseDir);
                        $processor->rollback($output);
                    }
                }
            }
        }
    }
}
