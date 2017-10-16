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
use Sjorek\Composer\VirtualEnvironment\Config\Command\SymbolicLinkConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface;
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
                new InputArgument('link', InputOption::VALUE_OPTIONAL, 'Symbolic link to add.'),
                new InputOption('save-local', null, InputOption::VALUE_NONE, 'Store links locally in "composer.venv".'),
                new InputOption('save-global', null, InputOption::VALUE_NONE, 'Store links globally in "' . $home .'/composer.venv".'),
                new InputOption('skip-local', null, InputOption::VALUE_NONE, 'Ignore the local configuration.'),
                new InputOption('skip-global', null, InputOption::VALUE_NONE, 'Ignore the global configuration.'),
                new InputOption('remove', null, InputOption::VALUE_NONE, 'Remove any deployed symbolic links.'),
                new InputOption('force', "f", InputOption::VALUE_NONE, 'Force overwriting existing symbolic links.'),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment:link</info> command places 
symlinks to php- and composer-binaries in the bin directory.

Example:

    <info>php composer.phar venv:symlink ${example}</info>

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
        $config->persist($config->get('force'));
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
