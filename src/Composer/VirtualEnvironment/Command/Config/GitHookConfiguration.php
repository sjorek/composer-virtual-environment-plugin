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
use Sjorek\Composer\VirtualEnvironment\Processor\GitHook\GitHookProcessorInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class GitHookConfiguration extends AbstractCommandConfiguration
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
        if ($input->getOption('script')) {
            $config['script'] = $input->getOption('script');
            if ($input->getOption('shebang')) {
                $config['shebang'] = $input->getOption('shebang');
            }
        } elseif ($input->getOption('file')) {
            $config['file'] = $input->getOption('file');
        } elseif ($input->getOption('link')) {
            $config['link'] = $input->getOption('link');
        } elseif ($input->getOption('url')) {
            $config['url'] = $input->getOption('url');
        }

        $hooks = array();
        $hooks_expanded = array();
        if ($input->getArgument('hook')) {
            foreach ($input->getArgument('hook') as $hook) {
                $hooks[$hook] = $config;
            }
            if ($input->getOption('add')) {
                $hooks = array_merge($recipe->get('git-hook', array()), $hooks);
            }
        } elseif ($recipe->has('git-hook')) {
            $hooks = $recipe->get('git-hook');
        }

        foreach ($hooks as $hook => $config) {
            if (!in_array($hook, GitHookProcessorInterface::GIT_HOOKS, true)) {
                $output->writeln(
                    sprintf(
                        '<error>Invalid git-hook given: %s</error>',
                        $hook
                    ),
                    OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                );

                return false;
            }
            if (empty($config)) {
                $output->writeln(
                    sprintf(
                        '<error>Missing or invalid git-hook type configuration for hook %s.</error>',
                        $hook
                    ),
                    OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                );

                return false;
            }
            $hooks_expanded[$hook] = $this->expandConfig($config, false);
        }
        $this->set('git-hook', $hooks);
        $this->set('git-hook-expanded', $hooks_expanded);

        return true;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareSave()
     */
    protected function prepareSave(FileConfigurationInterface $recipe)
    {
        $recipe->set('git-hook', $this->get('git-hook') ?: new \stdClass());

        return $recipe;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareLock()
     */
    protected function prepareLock(FileConfigurationInterface $recipe)
    {
        $recipe->set('git-hook', $this->get('git-hook') ?: new \stdClass());
        $recipe->set('git-hook-expanded', $this->get('git-hook-expanded') ?: new \stdClass());

        return $recipe;
    }
}
