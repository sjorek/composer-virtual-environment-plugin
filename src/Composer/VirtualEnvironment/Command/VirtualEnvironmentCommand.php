<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephnan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Util\Platform;
use Sjorek\Composer\VirtualEnvironment\Config\CommandConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class VirtualEnvironmentCommand extends BaseCommand
{
    protected function configure()
    {
        $io = $this->getIO();
        $composerFile = Factory::getComposerFile();
        $composerJson = new JsonFile($composerFile, null, $io);
        $manifest = $composerJson->read();

        $name = isset($manifest['name']) ? $manifest['name'] : null;
        $composer = realpath($_SERVER['argv'][0]) ?: null;

        $this
            ->setName('virtual-environment')
            ->setAliases(array('virtualenvironment', 'venv'))
            ->setDescription('Setup or teardown a virtual environment, with shell activation scripts and/or symbolic links to php and composer.')
            ->setDefinition(array(
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the virtual environment.', $name),
                new InputOption('shell', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Set the list of shell activators to deploy.'),
                new InputOption('php', null, InputOption::VALUE_REQUIRED, 'Add symlink to php.'),
                new InputOption('composer', null, InputOption::VALUE_REQUIRED, 'Add symlink to composer.', $composer),
                new InputOption('update-local', null, InputOption::VALUE_NONE, 'Update the local virtual environment configuration recipe in "./composer.venv".'),
                new InputOption('update-global', null, InputOption::VALUE_NONE, 'Update the global virtual environment configuration recipe in "~/.composer/composer.venv".'),
                new InputOption('ignore-local', null, InputOption::VALUE_NONE, 'Ignore the local virtual environment configuration recipe in "./composer.venv".'),
                new InputOption('ignore-global', null, InputOption::VALUE_NONE, 'Ignore the global virtual environment configuration recipe in "~/.composer/composer.venv".'),
                new InputOption('remove', null, InputOption::VALUE_NONE, 'Remove any deployed shell activators or symbolic links.'),
                new InputOption('force', "f", InputOption::VALUE_NONE, 'Force overwriting existing environment scripts'),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment</info> command creates files to activate
and deactivate the current bin directory in shell,
optionally placing symlinks to php- and composer-binaries
in the bin directory.

Usage:

    <info>php composer.phar virtual-environment</info>

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = new CommandConfiguration(
            $input,
            $output,
            $this->getComposer(),
            $this->getIO()
        );
        if ($input->getOption('remove')) {
            $this->rollback($input, $output, $config);
        } else {
            $this->deploy($input, $output, $config);
        }
    }

    protected function deploy(InputInterface $input, OutputInterface $output, ConfigurationInterface $config)
    {
        $data = array(
            '@NAME@' => $config->get('name'),
            '@BASE_DIR@' => $config->get('basePath'),
            '@BIN_DIR@' => $config->get('binPath'),
        );

        $activators = $config->get('activators');
        if (empty($activators)) {
            $output->writeln('Skipping creation shell activators, none available.');
        } else {
            $activators = Processor\ActivationScriptProcessor::export($activators);
            foreach ($activators as $filename) {
                $source = $config->get('resPath') . DIRECTORY_SEPARATOR .$filename;
                $target = $config->get('binPath') . DIRECTORY_SEPARATOR .$filename;
                $processor = new Processor\ActivationScriptProcessor($source, $target, $data);
                $processor->deploy($output, $input->getOption('force'));
            }
        }

        $symlinks = $config->get('symlinks');
        if (empty($symlinks)) {
            $output->writeln('Skipping creation of symbolic links, none available.');
        } elseif (Platform::isWindows()) {
            $output->writeln('    <warning>Symbolic links are not supported on windows</warning>');
        } else {
            foreach ($symlinks as $name => $source) {
                $target = $config->get('binPath') . DIRECTORY_SEPARATOR . $name;
                $processor = new Processor\SymbolicLinkProcessor($source, $target);
                $processor->deploy($output, $input->getOption('force'));
            }
        }
        $config->persist($input->getOption('force'));
    }

    protected function rollback(InputInterface $input, OutputInterface $output, ConfigurationInterface $config)
    {
        $activators = $config->get('activators');
        if (empty($activators)) {
            $output->writeln('Skipping removal of shell activators, none available.');
        } else {
            $activators = Processor\ActivationScriptProcessor::export($activators);
            foreach ($activators as $filename) {
                $source = $config->get('resPath') . DIRECTORY_SEPARATOR .$filename;
                $target = $config->get('binPath') . DIRECTORY_SEPARATOR .$filename;
                $processor = new Processor\ActivationScriptProcessor($source, $target, array());
                $processor->rollback($output);
            }
        }

        $symlinks = $config->get('symlinks');
        if (empty($symlinks)) {
            $output->writeln('Skipping removal of symbolic links, none available.');
        } elseif (!Platform::isWindows()) {
            foreach ($symlinks as $name => $source) {
                $target = $config->get('binPath') . DIRECTORY_SEPARATOR . $name;
                $processor = new Processor\SymbolicLinkProcessor($source, $target);
                $processor->rollback($output);
            }
        }
    }
}
