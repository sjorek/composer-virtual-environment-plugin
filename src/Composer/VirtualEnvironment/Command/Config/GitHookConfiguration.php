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
use Sjorek\Composer\VirtualEnvironment\Config\GlobalConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\LocalConfiguration;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class GitHookConfiguration extends AbstractCommandConfiguration
{
    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareLoad()
     */
    protected function prepareLoad(
        FileConfigurationInterface $load = null,
        FileConfigurationInterface $save = null
    ) {
//         $input = $this->input;
//         if (!$input->getArgument('hook')) {
//             $recipe = new LocalConfiguration($this->composer);
//             if ($recipe->load()) {
//                 $this->recipe = $recipe;
//                 $this->set('load', true);
//                 $this->set('save', false);
//             } else {
//                 $recipe = new GlobalConfiguration($this->composer);
//                 if ($recipe->load()) {
//                     $this->recipe = $recipe;
//                     $this->set('load', true);
//                     $this->set('save', false);
//                 }
//             }
//         }
        return true;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::finishLoad()
     */
    protected function finishLoad(FileConfigurationInterface $recipe)
    {
        $input = $this->input;
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
        $config_expanded = $this->expandConfig($config, false);

        $hooks = $recipe->get('git-hook', array());
        $hooks_expanded = array();
        if ($input->getArgument('hook')) {
            foreach ($input->getArgument('hook') as $hook) {
                $hooks[$hook] = $config;
                $hooks_expanded[$hook] = $config_expanded;
            }
        }
        if (empty($config)) {
            foreach ($hooks as $hook => $config) {
                if (!isset($hooks_expanded[$hook])) {
                    $hooks_expanded[$hook] = $this->expandConfig($config, false);
                }
            }
        } else {
            foreach (array_keys($hooks) as $hook) {
                if (!isset($hooks_expanded[$hook])) {
                    $hooks_expanded[$hook] = $config_expanded;
                }
            }
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
        $recipe->set('git-hook', $this->get('git-hook'));

        return $recipe;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareLock()
     */
    protected function prepareLock(FileConfigurationInterface $recipe)
    {
        $recipe->set('git-hook', $this->get('git-hook-expanded'));

        return $recipe;
    }
}
