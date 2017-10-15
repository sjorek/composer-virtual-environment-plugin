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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $example = implode(
            PATH_SEPARATOR,
            array(
                implode('/', array('relative', 'path', 'to', 'symlink')),
                implode('/', array('','absolute','path','to','symlink','target')),
            )
        );

        $config = $this->getComposer()->getConfig();
        $binDir = $config->get('bin-dir', Config::RELATIVE_PATHS);
        $home = $config->get('home');

        $symlinks = null;
        $composerPhar = realpath($_SERVER['argv'][0]) ?: null;
        if ($composerPhar) {
            $symlinks = array($binDir . '/composer.phar' . PATH_SEPARATOR . $composerPhar);
        }

        $this
            ->setName('venv:link')
            ->setDescription('Add or remove virtual environment symbolic links.')
            ->setDefinition(array(
                new InputOption('link', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Add symbolic link to "' . $example . '".', $symlinks),
                new InputOption('update-local', null, InputOption::VALUE_NONE, 'Update the local configuration in "composer.venv".'),
                new InputOption('ignore-local', null, InputOption::VALUE_NONE, 'Ignore the local configuration in "composer.venv".'),
                new InputOption('update-global', null, InputOption::VALUE_NONE, 'Update the global configuration in "' . $home .'/composer.venv".'),
                new InputOption('ignore-global', null, InputOption::VALUE_NONE, 'Ignore the global configuration in "' . $home .'/composer.venv".'),
                new InputOption('remove', null, InputOption::VALUE_NONE, 'Remove any deployed symbolic links.'),
                new InputOption('force', "f", InputOption::VALUE_NONE, 'Force overwriting existing symbolic links.'),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment:symbolic-link</info> command places 
symlinks to php- and composer-binaries in the bin directory.

Usage:

    <info>php composer.phar venv:symlink</info>

After this you can use the linked binaries in composer's <info>run-script</info>
or the in <info>virtual-environment:shell</info>.

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

    protected function deploy(InputInterface $input, OutputInterface $output, ConfigurationInterface $config)
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
            foreach ($symlinks as $source => $target) {
                $processor = new Processor\SymbolicLinkProcessor($source, $target);
                $processor->deploy($output, $input->getOption('force'));
            }
        }
        $config->persist($input->getOption('force'));
    }

    protected function rollback(InputInterface $input, OutputInterface $output, ConfigurationInterface $config)
    {
        $symlinks = $config->get('link');
        if (empty($symlinks)) {
            $output->writeln(
                '<comment>Skipping removal of symbolic links, as none is available.</comment>'
            );
        } elseif (!Platform::isWindows()) {
            foreach ($symlinks as $source => $target) {
                $processor = new Processor\SymbolicLinkProcessor($source, $target);
                $processor->rollback($output);
            }
        }
    }
}
