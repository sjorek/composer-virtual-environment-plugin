<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command\Config;

use Sjorek\Composer\VirtualEnvironment\Config\FileConfigurationInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\ShellHookProcessorInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellHookConfiguration extends AbstractCommandConfiguration
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
        }
        if ($input->getOption('shell')) {
            $config['shell'] = $input->getOption('shell');
        }
        if ($input->getOption('script')) {
            $config['script'] = $input->getOption('script');
        } elseif ($input->getOption('file')) {
            $config['file'] = $input->getOption('file');
        } elseif ($input->getOption('link')) {
            $config['link'] = $input->getOption('link');
        } elseif ($input->getOption('url')) {
            $config['url'] = $input->getOption('url');
        }

        $hooks = $input->getOption('add') ? $recipe->get('shell-hook', array()) : array();
        $hooks_expanded = array();
        if ($input->getArgument('hook')) {
            foreach ($input->getArgument('hook') as $hook) {
                if (!isset($hooks[$hook])) {
                    $hooks[$hook] = array();
                }
                if (!isset($config['priority'])) {
                    $config['priority'] = 0;
                }
                if (!isset($config['name'])) {
                    $config['name'] = null;
                }
                if (empty($config) || count($config) !== 4) {
                    $output->writeln(
                        sprintf(
                            '<error>Missing or invalid shell-hook configuration for hook %s.</error>',
                            $hook
                        ),
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );

                    return false;
                }
                if ($config['name'] === null) {
                    $name = sprintf('%02d-%s', $config['priority'], basename($config['shell']));
                } else {
                    $name = sprintf('%02d-%s', $config['priority'], $config['name']);
                }
                unset($config['priority'], $config['name']);
                $hooks[$hook][$name] = $config;
            }
        } elseif ($recipe->has('shell-hook')) {
            $hooks = $recipe->get('shell-hook');
        }

        foreach ($hooks as $hook => $hookConfigs) {
            foreach ($hookConfigs as $name => $config) {
                if (!in_array($hook, ShellHookProcessorInterface::SHELL_HOOKS, true)) {
                    $output->writeln(
                        sprintf(
                            '<error>Invalid shell-hook given: %s</error>',
                            $hook
                        ),
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );

                    return false;
                }
                if (empty($config) || count($config) !== 2) {
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
                $hooks_expanded[$hook][$name] = $this->expandConfig($config, false);
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
