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
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Util\Platform;
use Sjorek\Composer\VirtualEnvironment\Command\Config\SymbolicLinkConfiguration;
use Sjorek\Composer\VirtualEnvironment\Command\Config\ConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $config = $this->getComposer()->getConfig();
        $binDir = $config->get('bin-dir', Config::RELATIVE_PATHS);
        $home = $config->get('home');

        $composerPhar = null;
        if (
            isset($_SERVER['argv'][0]) &&
            realpath($_SERVER['argv'][0]) &&
            substr(realpath($_SERVER['argv'][0]), -1 * strlen('/composer.phar')) === '/composer.phar'
        ) {
            $composerPhar = realpath($_SERVER['argv'][0]);
        }

        $example = implode(
            PATH_SEPARATOR,
            array(
                $binDir . '/composer.phar',
                $composerPhar ?: '../../composer.phar',
            )
        );

        $this
            ->setName('virtual-environment:link')
            ->setAliases(array('venv:link'))
            ->setDescription('Add or remove virtual environment symbolic links.')
            ->setDefinition(array(
                new InputArgument('link', InputOption::VALUE_OPTIONAL, 'List of symbolic links to add.'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Use given configuration file.'),
                new InputOption('local', 'l', InputOption::VALUE_NONE, 'Use local configuration file "./composer.venv".'),
                new InputOption('global', 'g', InputOption::VALUE_NONE, 'Use global configuration file "' . $home .'/composer.venv".'),
                new InputOption('save', 's', InputOption::VALUE_NONE, 'Save configuration file.'),
                new InputOption('remove', 'r', InputOption::VALUE_NONE, 'Remove any deployed symbolic links.'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force overwriting existing symbolic links'),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment:link</info> command places 
symlinks to php- and composer-binaries in the bin directory.

Example:

    <info>php composer.phar venv:link ${example}</info>

After this you can use the linked binaries in composer's <info>run-script</info>
or in <info>virtual-environment:shell</info>.

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
        return new SymbolicLinkConfiguration($input, $output, $composer, $io);
    }

    protected function deploy(ConfigurationInterface $config, OutputInterface $output)
    {
        $symlinks = $config->get('link');
        if (empty($symlinks)) {
            $output->writeln(
                '<comment>Skipping creation of symbolic links, as none is available.</comment>'
            );
        } elseif (Platform::isWindows()) {
            $output->writeln(
                '<warning>Symbolic links are not (yet) supported on windows.</warning>'
            );
        } else {
            $basePath = $config->get('basePath', '');
            foreach ($symlinks as $source => $target) {
                $processor = new Processor\SymbolicLinkProcessor($source, $target, $basePath);
                $processor->deploy($output, $config->get('force'));
            }
        }
        $config->save($config->get('force'));
    }

    protected function rollback(ConfigurationInterface $config, OutputInterface $output)
    {
        $symlinks = $config->get('link');
        if (empty($symlinks)) {
            $output->writeln(
                '<comment>Skipping removal of symbolic links, as none is available.</comment>'
            );
        } elseif (!Platform::isWindows()) {
            $basePath = $config->get('basePath', '');
            foreach ($symlinks as $source => $target) {
                $processor = new Processor\SymbolicLinkProcessor($source, $target, $basePath);
                $processor->rollback($output);
            }
        }
    }
}
