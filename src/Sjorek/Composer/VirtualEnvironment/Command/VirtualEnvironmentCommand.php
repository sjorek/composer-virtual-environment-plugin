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
use Sjorek\Composer\VirtualEnvironment\Util\CommandConfiguration;
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
        $cmdConfig = new CommandConfiguration();

        $io = $this->getIO();
        $recipe = Factory::getComposerFile();
        $json = new JsonFile($recipe, null, $io);
        $manifest = $json->read();
        $name = $cmdConfig->get('name', $manifest['name']);

        if (getenv('COMPOSER_VIRTUAL_ENVIRONMENT')) {
            $php = $cmdConfig->get('php');
            $composer = $cmdConfig->get('composer');
        } else {
            $php = $cmdConfig->get('php', exec('which php') ?: null);
            $composer = $cmdConfig->get('composer', realpath($_SERVER['argv'][0]) ?: null);
        }

        $this
            ->setName('virtual-environment')
            ->setDescription('Setup a virtual environment.')
            ->setDefinition(array(
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the virtual environment', $name),
                new InputOption('php', null, InputOption::VALUE_OPTIONAL, 'Add symlink to php', $php),
                new InputOption('composer', null, InputOption::VALUE_OPTIONAL, 'Add symlink to composer', $composer),
                new InputOption('force', "f", InputOption::VALUE_OPTIONAL, 'Force overwriting existing environment scripts', false),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment</info> command creates files to activate
and deactivate the current bin directory in shell,
optionally placing a symlinks to php- and composer-binaries
in the bin directory.

<info>php composer.phar virtual-environment</info>

After this you can source the activation-script
corresponding to your shell:

bash/zsh:

    <info>$ source vendor/bin/activate</info>

csh:

    <info>$ source vendor/bin/activate.csh</info>

fish:

    <info>$ . vendor/bin/activate.fish</info>

bash (alternative):

    <info>$ source vendor/bin/activate.bash</info>

zsh (alternative):

    <info>$ source vendor/bin/activate.zsh</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmdConfig = new CommandConfiguration();
        $composer = $this->getComposer();
        $config = $composer->getConfig();
        $recipe = Factory::getComposerFile();
        $io = $this->getIO();

        $filesystem = new Filesystem();
        $basePath = $filesystem->normalizePath(realpath(realpath(dirname($recipe))));
        $binPath = $filesystem->normalizePath($config->get('bin-dir'));
        $resPath = $filesystem->normalizePath(__DIR__ . '/../../../../../res');

        $json = new JsonFile($recipe, null, $io);
        $manifest = $json->read();

        if ($input->getOption('name')) {
            $name = $input->getOption('name');
        } else {
            $name = $manifest['name'];
        }

        $data = array(
            '@NAME@' => $name,
            '@BASE_DIR@' => $basePath,
            '@BIN_DIR@' => $binPath,
        );

        $activators = array(
            'activate',
            'activate.bash',
            'activate.csh',
            'activate.fish',
            'activate.zsh',
        );
        foreach ($activators as $key => $filename) {
            $source = $resPath . '/' .$filename;
            $target = $binPath . '/' .$filename;
            $processor = new Processor\ActivationScriptProcessor($source, $target, $data);
            if ($processor->deploy($output, $input->getOption('force'))) {
                continue;
            }
            unset($activators[$key]);
        }
        if (!empty($activators)) {
            $cmdConfig->set('activators', implode(',', $activators));
        }

        $symlinks = array();
        if ($input->getOption('php')) {
            $symlinks['php'] = realpath($input->getOption('php')) ?: $input->getOption('php');
        }
        if ($input->getOption('composer')) {
            $symlinks['composer'] = realpath($input->getOption('composer')) ?: $input->getOption('composer');
        }
        if (!empty($symlinks) && Platform::isWindows()) {
            $output->writeln('    <warning>Skipped creation of symbolic links on windows</warning>');

            return ;
        }
        foreach ($symlinks as $name => $source) {
            $target = $binPath . '/' .$name;
            $processor = new Processor\SymbolicLinkProcessor($source, $target);
            if ($processor->deploy($output, $input->getOption('force'))) {
                $cmdConfig->set($name, $source);
            }
        }
        if ($cmdConfig->persist()) {
            $output->writeln('Updated virtual environment configuration file: composer.venv');
        }
    }
}
