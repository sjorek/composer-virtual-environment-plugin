<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command\Config;

use Sjorek\Composer\VirtualEnvironment\Config\FileConfigurationInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivatorHookConfiguration extends AbstractCommandConfiguration
{
    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::setup()
     */
    protected function setup()
    {
        $recipe = $this->recipe;
        $input = $this->input;
        $output = $this->output;

        $config = array();
        if ($input->getOption('name')) {
            $config['name'] = $input->getOption('name');
        }
        if ($input->getOption('priority')) {
            $config['priority'] = min(max(0, (int) $input->getOption('priority')), 99);
        } else {
            $config['priority'] = 0;
        }
        if ($input->getOption('shell')) {
            $config['shell'] = $input->getOption('shell');
        }
        if ($input->getOption('script')) {
            $config['script'] = $input->getOption('script');
        }

        $hooks = $input->getOption('add') ? $recipe->get('shell-hook', array()) : array();
        $hooks_expanded = array();
        if ($input->getArgument('hook')) {
            foreach ($input->getArgument('hook') as $hook) {
                if (!isset($hooks[$hook])) {
                    $hooks[$hook] = array();
                }
                $hooks[$hook][] = $config;
            }
        } elseif ($recipe->has('shell-hook')) {
            $hooks = $recipe->get('shell-hook');
        }

        foreach ($hooks as $hook => $hookConfigs) {
            foreach ($hookConfigs as $config) {
                if (!in_array($hook, ShellActivationHookProcessor::SHELL_HOOKS, true)) {
                    $output->writeln(
                        sprintf(
                            '<error>Invalid shell-hook given: %s</error>',
                            $hook
                        ),
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );

                    return false;
                }
                if (empty($config)) {
                    $output->writeln(
                        sprintf(
                            '<error>Missing or invalid shell-hook configuration for hook %s.</error>',
                            $hook
                        ),
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );

                    return false;
                }
                if (!isset($hooks_expanded[$hook])) {
                    $hooks_expanded[$hook] = array();
                }
                $hooks_expanded[$hook][] = $this->expandConfig($config, false);
            }
        }
        $this->set('shell-hook', $hooks);
        $this->set('shell-hook-expanded', $hooks_expanded);

        return true;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareSave()
     */
    protected function prepareSave(FileConfigurationInterface $recipe)
    {
        $recipe->set('shell-hook', $this->get('shell-hook') ?: new \stdClass());

        return $recipe;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareLock()
     */
    protected function prepareLock(FileConfigurationInterface $recipe)
    {
        $recipe->set('shell-hook', $this->get('shell-hook') ?: new \stdClass());
        $recipe->set('shell-hook-expanded', $this->get('shell-hook-expanded') ?: new \stdClass());

        return $recipe;
    }
}
