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
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Util\Platform;
use Sjorek\Composer\VirtualEnvironment\Config\Command\ShellActivatorConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivatorCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $io = $this->getIO();
        $composerFile = Factory::getComposerFile();
        $home = $this->getComposer()->getConfig()->get('home');

        $name = dirname(getcwd());
        if (file_exists($composerFile)) {
            $composerJson = new JsonFile($composerFile, null, $io);
            $manifest = $composerJson->read();
            if (isset($manifest['name'])) {
                $name = $manifest['name'];
            }
        }

        $this
            ->setName('venv:shell')
            ->setDescription('Setup or teardown virtual environment shell activation scripts.')
            ->setDefinition(array(
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the virtual environment.', $name),
                new InputOption('shell', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Set the list of shell activators to deploy.', array('detect')),
                new InputOption('color-prompt', null, InputOption::VALUE_NONE, 'Enable the color prompt per default. Works currently only for "bash".'),
                new InputOption('update-local', null, InputOption::VALUE_NONE, 'Update the local configuration recipe in "./composer.venv".'),
                new InputOption('ignore-local', null, InputOption::VALUE_NONE, 'Ignore the local configuration recipe in "./composer.venv".'),
                new InputOption('update-global', null, InputOption::VALUE_NONE, 'Update the global configuration in "' . $home .'/composer.venv".'),
                new InputOption('ignore-global', null, InputOption::VALUE_NONE, 'Ignore the global configuration in "' . $home .'/composer.venv".'),
                new InputOption('remove', null, InputOption::VALUE_NONE, 'Remove any deployed shell activation scripts.'),
                new InputOption('force', "f", InputOption::VALUE_NONE, 'Force overwriting existing environment files and links'),
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
    protected function deploy(InputInterface $input, OutputInterface $output, ConfigurationInterface $config)
    {
        $activators = $config->get('shell');
        if (empty($activators)) {
            $output->writeln(
                '<comment>Skipping creation of shell activators, none available.</comment>'
            );
        } else {
            $data = array(
                '@NAME@' => $config->get('name'),
                '@BASE_DIR@' => $config->get('basePath'),
                '@BIN_DIR@' => $config->get('binPath'),
                '@COLOR_PROMPT@' => $config->get('color-prompt') ? '1' : '',
            );
            if (in_array('bash', $activators)) {
                $bash = 'bash';
                if (isset($_SERVER['SHELL']) && basename($_SERVER['SHELL']) === 'bash') {
                    $bash = $_SERVER['SHELL'];
                } elseif (isset($_ENV['SHELL']) && basename($_ENV['SHELL']) === 'bash') {
                    $bash = $_ENV['SHELL'];
                }
                // TODO check that $bash is really a bash? check version or issue a command only bash supports!
                foreach(self::BASH_TEMPLATE_COMMANDS as $key => $command) {
                    $data[$key] = exec(
                        sprintf(
                            '( echo %s | %s -ls ) 2>/dev/null',
                            escapeshellarg($command),
                            escapeshellcmd($bash)
                        )
                    );
                }
            }
            $activators = Processor\ActivationScriptProcessor::export($activators);
            foreach ($activators as $filename) {
                $source = $config->get('resPath') . '/' . $filename;
                $target = $config->get('binPath') . '/' . $filename;
                $processor = new Processor\ActivationScriptProcessor($source, $target, $data);
                $processor->deploy($output, $input->getOption('force'));
            }
            if ($config->has('link')) {
                $symlinks = $config->get('link');
                if (empty($symlinks)) {
                    $output->writeln(
                        '<comment>Skipping creation of symbolic link to shell activation script, as none is available.</comment>'
                    );
                } elseif (Platform::isWindows()) {
                    $output->writeln(
                        '<warning>Symbolic link to shell activation script is not (yet) supported on windows.</warning>'
                    );
                } else {
                    foreach ($symlinks as $source => $target) {
                        $processor = new Processor\SymbolicLinkProcessor($source, $target);
                        $processor->deploy($output, $input->getOption('force'));
                    }
                }
            }
        }

        $config->persist($input->getOption('force'));
    }

    protected function rollback(InputInterface $input, OutputInterface $output, ConfigurationInterface $config)
    {
        $activators = $config->get('activators');
        if (empty($activators)) {
            $output->writeln(
                '<comment>Skipping removal of shell activation scripts, as none is available.</comment>'
            );
        } else {
            $activators = Processor\ActivationScriptProcessor::export($activators);
            foreach ($activators as $filename) {
                $source = $config->get('resPath') . DIRECTORY_SEPARATOR .$filename;
                $target = $config->get('binPath') . DIRECTORY_SEPARATOR .$filename;
                $processor = new Processor\ActivationScriptProcessor($source, $target, array());
                $processor->rollback($output);
            }
            if ($config->has('link')) {
                $symlinks = $config->get('link');
                if (empty($symlinks)) {
                    $output->writeln(
                        '<comment>Skipping removal of symbolic link to shell activation script, as none is available.</comment>'
                    );
                } elseif (Platform::isWindows()) {
                    $output->writeln(
                        '<warning>Symbolic link to shell activation script is not (yet) supported on windows.</warning>'
                    );
                } else {
                    foreach ($symlinks as $source => $target) {
                        $processor = new Processor\SymbolicLinkProcessor($source, $target);
                        $processor->deploy($output, $input->getOption('force'));
                    }
                }
            }
        }
    }
}
