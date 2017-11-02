<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
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
use Sjorek\Composer\VirtualEnvironment\Command\Config\SymbolicLinkConfiguration;
use Sjorek\Composer\VirtualEnvironment\Command\Config\CommandConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $composerPhar = null;
        if (isset($_SERVER['argv'])) {
            foreach ($_SERVER['argv'] as $argument) {
                $argument = realpath($argument);
                if ($argument &&
                    substr($argument, -1 * strlen('/composer.phar')) === '/composer.phar'
                ) {
                    $composerPhar = $argument;
                    break;
                }
            }
        }

        $example = implode(
            PATH_SEPARATOR,
            array(
                escapeshellarg('{$bin-dir}/composer'),
                escapeshellarg($composerPhar ?: '{$bin-dir-up}/composer.phar'),
            )
        );

        $this
            ->setName('virtual-environment:link')
            ->setAliases(array('venv:link'))
            ->setDescription('Add or remove virtual environment symbolic links.')
            ->setDefinition(
                $this->addDefaultDefinition(
                    array(
                        new InputArgument(
                            'link',
                            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                            'List of symbolic links to add or remove.'
                        ),
                    )
                )
            )
            ->setHelp(
                <<<EOT
The <info>virtual-environment:link</info> command places symlinks
to php- and composer-binaries in the bin directory.

Example:

    <info>php composer.phar venv:link ${example}</info>

After this you can use the linked binaries in composer
<info>run-script</info> or in <info>virtual-environment:shell</info>.

Attention: only link the composer like in the example above,
if your project does not require the <info>composer/composer</info> package.

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

    /**
     * {@inheritDoc}
     * @see AbstractProcessorCommand::deploy()
     */
    protected function deploy(CommandConfigurationInterface $config, OutputInterface $output)
    {
        $symlinks = $config->get('link-expanded');
        if (empty($symlinks)) {
            $output->writeln(
                '<error>Skipping creation of symbolic links, as none is available.</error>'
            );
        } elseif (Platform::isWindows()) {
            $output->writeln(
                '<error>Symbolic links are not (yet) supported on windows.</error>',
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );
        } else {
            $baseDir = $config->get('base-dir', '');
            foreach ($symlinks as $source => $target) {
                $processor = new Processor\SymbolicLinkProcessor($source, $target, $baseDir);
                $processor->deploy($output, $config->get('force'));
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see AbstractProcessorCommand::rollback()
     */
    protected function rollback(CommandConfigurationInterface $config, OutputInterface $output)
    {
        $symlinks = $config->get('link-expanded');
        if (empty($symlinks)) {
            $output->writeln(
                '<error>Skipping removal of symbolic links, as none is available.</error>'
            );
        } elseif (Platform::isWindows()) {
            $output->writeln(
                '<error>Symbolic links are not (yet) supported on windows.</error>',
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );
        } else {
            $baseDir = $config->get('base-dir', '');
            foreach ($symlinks as $source => $target) {
                $processor = new Processor\SymbolicLinkProcessor($source, $target, $baseDir);
                $processor->rollback($output);
            }
        }
    }
}
