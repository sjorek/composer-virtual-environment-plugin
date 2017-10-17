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

use Sjorek\Composer\VirtualEnvironment\Config\GlobalConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\LocalConfiguration;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class GitHookConfiguration extends AbstractConfiguration
{
    public function load()
    {
        $input = $this->input;

        if (!$input->getArgument('hook')) {
            $recipe = new LocalConfiguration($this->composer);
            if ($recipe->load()) {
                $this->recipe = $recipe;
                $this->set('load', true);
                $this->set('save', false);
            } else {
                $recipe = new GlobalConfiguration($this->composer);
                if ($recipe->load()) {
                    $this->recipe = $recipe;
                    $this->set('load', true);
                    $this->set('save', false);
                }
            }
        }

        if (!parent::load()) {
            return false;
        }

        $recipe = $this->recipe;

        $hooks = array();
        if ($input->getArgument('hook')) {
            foreach ($input->getArgument('hook') as $hook) {
                if (strpos($hook, PATH_SEPARATOR) === false) {
                    $this->output->writeln(
                        sprintf(
                            '<error>Invalid hook %s given. Hook format is: hook-name:"script to execute"</error>',
                            $hook
                        )
                    );

                    return false;
                }
                list($name, $script) = explode(PATH_SEPARATOR, $hook, 2);
                $hooks[$name] = $script;
            }
        } elseif ($recipe->has('git-hook')) {
            $hooks = $recipe->get('git-hook');
        }
        $this->set('git-hook', $hooks);
        $this->set('git-hook-expanded', $this->expandConfig($hooks));

        return true;
    }

    public function save($force = false)
    {
        if ($this->get('save')) {
            $recipe = $this->recipe;
            $recipe->set('git-hook', $this->get('git-hook'));
        }

        return parent::save($force);
    }
}
