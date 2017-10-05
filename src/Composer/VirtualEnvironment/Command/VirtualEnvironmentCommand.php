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
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Sjorek\Composer\VirtualEnvironment\Config;
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

        $name = $manifest['name'];
        $activators = explode(',', Processor\ActivationScriptProcessor::AVAILABLE_ACTIVATORS);
        $composer = realpath($_SERVER['argv'][0]) ?: null;

        $this
            ->setName('virtual-environment')
            ->setDescription('Setup a virtual environment.')
            ->setDefinition(array(
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the virtual environment.', $name),
                new InputOption('shell', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Set the list of shell activators to deploy.', $activators),
                new InputOption('php', null, InputOption::VALUE_REQUIRED, 'Add symlink to php.'),
                new InputOption('composer', null, InputOption::VALUE_REQUIRED, 'Add symlink to composer.', $composer),
                new InputOption('update-local', null, InputOption::VALUE_NONE, 'Update the local virtual environment configuration recipe in "./composer.venv".'),
                new InputOption('update-global', null, InputOption::VALUE_NONE, 'Update the global virtual environment configuration recipe in "~/.composer/composer.venv".'),
                new InputOption('ignore-local', null, InputOption::VALUE_NONE, 'Ignore the local virtual environment configuration recipe in "./composer.venv".'),
                new InputOption('ignore-global', null, InputOption::VALUE_NONE, 'Ignore the global virtual environment configuration recipe in "~/.composer/composer.venv".'),
                new InputOption('force', "f", InputOption::VALUE_NONE, 'Force overwriting existing environment scripts'),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment</info> command creates files to activate
and deactivate the current bin directory in shell,
optionally placing a symlinks to php- and composer-binaries
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
        $composer = $this->getComposer();
        $config = $composer->getConfig();

        $io = $this->getIO();
        $composerFile = Factory::getComposerFile();
        $composerJson = new JsonFile($composerFile, null, $io);
        $manifest = $composerJson->read();

        $recipe = new Config\CompositeConfiguration(
            $composer,
            $input->getOption('update-local'),
            $input->getOption('ignore-local'),
            $input->getOption('update-global'),
            $input->getOption('ignore-global')
        );

        $filesystem = new Filesystem();
        $basePath = $filesystem->normalizePath(realpath(realpath(dirname($composerFile))));
        $binPath = $filesystem->normalizePath($config->get('bin-dir'));
        $resPath = $filesystem->normalizePath(__DIR__ . '/../../../../res');

        $name = $manifest['name'];
        if ($input->getOption('name')) {
            $name = $input->getOption('name');
        } else {
            $name = $recipe->get('name', $name);
        }
        $recipe->set('name', $name);

        $data = array(
            '@NAME@' => $name,
            '@BASE_DIR@' => $basePath,
            '@BIN_DIR@' => $binPath,
        );

        $candidates = explode(',', Processor\ActivationScriptProcessor::AVAILABLE_ACTIVATORS);
        if ($input->getOption('shell')) {
            $candidates = $input->getOption('shell');
        } else {
            $candidates = $recipe->get('shell', $candidates);
        }
        $activators = Processor\ActivationScriptProcessor::importConfiguration($candidates);
        foreach ($activators as $filename) {
            $source = $resPath . DIRECTORY_SEPARATOR .$filename;
            $target = $binPath . DIRECTORY_SEPARATOR .$filename;
            $processor = new Processor\ActivationScriptProcessor($source, $target, $data);
            $processor->deploy($output, $input->getOption('force'));
        }
        $activators = Processor\ActivationScriptProcessor::exportConfiguration($activators);
        $recipe->set('shell', $activators);

        $symlinks = array(
            'activate' => null,
            'composer' => null,
            'php' => null,
        );

        // If only has been given, we'll symlink to this activator
        if (count($activators) === 1) {
            $symlinks['activate'] = $binPath . DIRECTORY_SEPARATOR . 'activate.' . $activators[0];
        }
        if ($input->getOption('php')) {
            $symlinks['php'] = realpath($input->getOption('php')) ?: $input->getOption('php');
        } else {
            $symlinks['php'] = $recipe->get('php', $symlinks['php']);
        }
        if ($input->getOption('composer')) {
            $symlinks['composer'] = realpath($input->getOption('composer')) ?: $input->getOption('composer');
        } else {
            $symlinks['composer'] = $recipe->get('composer', $symlinks['composer']);
        }
        $symlinks = array_filter($symlinks);

        if (empty($symlinks)) {
            $output->writeln('Skipping creation of symbolic links, none available.');
        } elseif (Platform::isWindows()) {
            $output->writeln('    <warning>Symbolic links are not supported on windows</warning>');
        } else {
            foreach ($symlinks as $name => $source) {
                $target = $binPath . DIRECTORY_SEPARATOR . $name;
                $processor = new Processor\SymbolicLinkProcessor($source, $target);
                $processor->deploy($output, $input->getOption('force'));
                if ($input->getOption('update-local') && $name !== 'activate') {
                    $recipe->set($name, $source);
                }
            }
        }
        if ($recipe->updateLocal) {
            if ($recipe->local->persist($input->getOption('force'))) {
                $output->writeln('Update of local configuration "' . $recipe->local->filename . '" succeeded.');
            } else {
                $output->writeln('    <warning>Updated of local configuration "' . $recipe->local->filename . '" failed.<warning>');
            }
        }
        if ($recipe->updateGlobal) {
            if ($recipe->global->persist($input->getOption('force'))) {
                $output->writeln('Update of global configuration "' . $recipe->global->filename . '" succeeded.');
            } else {
                $output->writeln('    <warning>Updated of global configuration "' . $recipe->global->filename . '" failed.<warning>');
            }
        }
    }
}
