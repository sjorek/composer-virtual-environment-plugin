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
use Sjorek\Composer\VirtualEnvironment\Command\Config\CommandConfigurationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractProcessorCommand extends AbstractComposerCommand
{
    /**
     * @param  array $definition
     * @return array
     */
    protected function addDefaultDefinition(array $definition)
    {
        $home = $this->getComposer()->getConfig()->get('home');
        array_push(
            $definition,
            new InputOption(
                'add',
                'a',
                InputOption::VALUE_NONE,
                'Add to existing configuration.'
            ),
            new InputOption(
                'remove',
                'r',
                InputOption::VALUE_NONE,
                'Remove all configured items.'
            ),
            new InputOption(
                'save',
                's',
                InputOption::VALUE_NONE,
                'Save configuration.'
            ),
            new InputOption(
                'local',
                'l',
                InputOption::VALUE_NONE,
                'Use local configuration file "./composer-venv.json".'
            ),
            new InputOption(
                'global',
                'g',
                InputOption::VALUE_NONE,
                'Use global configuration file "' . $home .'/composer-venv.json".'
            ),
            new InputOption(
                'config-file',
                'c',
                InputOption::VALUE_REQUIRED,
                'Use given configuration file.'
            ),
            // new InputOption(
            //     'manifest',
            //     'm',
            //     InputOption::VALUE_NONE,
            //     'Use configuration from extra section of package manifest "./composer.json".'
            // ),
            new InputOption(
                'lock',
                null,
                InputOption::VALUE_NONE,
                'Lock configuration in "./composer-venv.lock".'
            ),
            new InputOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force overwriting existing git-hooks'
            )
        );

        return $definition;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getCommandConfiguration($input, $output, $this->getComposer(), $this->getIO());
        if ($config->load()) {
            if ($config->get('remove')) {
                $config->lock(true);
                $this->rollback($config, $output);
            } else {
                $this->deploy($config, $output);
                if ($config->get('save')) {
                    $config->save($config->get('force'));
                }
                if ($config->get('lock')) {
                    $config->lock();
                    $config->save($config->get('force'));
                }
            }
        } else {
            $output->writeln(
                '<error>Failed to load configuration.</error>',
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_NORMAL
            );
        }
    }

    /**
     * @param  InputInterface                $input
     * @param  OutputInterface               $output
     * @param  Composer                      $composer
     * @param  IOInterface                   $io
     * @return CommandConfigurationInterface
     */
    abstract protected function getCommandConfiguration(
        InputInterface $input,
        OutputInterface $output,
        Composer $composer,
        IOInterface $io
    );

    /**
     * @param CommandConfigurationInterface $config
     * @param OutputInterface               $output
     */
    abstract protected function deploy(
        CommandConfigurationInterface $config,
        OutputInterface $output
    );

    /**
     * @param CommandConfigurationInterface $config
     * @param OutputInterface               $output
     */
    abstract protected function rollback(
        CommandConfigurationInterface $config,
        OutputInterface $output
    );
}
