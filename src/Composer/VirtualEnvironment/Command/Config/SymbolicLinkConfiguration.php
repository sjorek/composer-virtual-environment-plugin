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
class SymbolicLinkConfiguration extends AbstractConfiguration
{
    public function load()
    {
        $input = $this->input;

        if (!$input->getArgument('link')) {
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

        $symlinks = array();
        if ($input->getArgument('link')) {
            foreach ($input->getArgument('link') as $link) {
                if (strpos($link, PATH_SEPARATOR) === false) {
                    $this->output->writeln(
                        sprintf(
                            '<error>Invalid link %s given. Link format is: path/to/link:path/to/target</error>',
                            $link
                        )
                    );

                    return false;
                }
                list($source, $target) = explode(PATH_SEPARATOR, $link, 2);
                $symlinks[$source] = $target;
            }
        } elseif ($recipe->has('link')) {
            $symlinks = $recipe->get('link');
        }
        // NOPE !
        // $symlinks = array_map('realpath', $symlinks);
        $this->set('link', $symlinks);

        return true;
    }

    public function save($force = false)
    {
        if ($this->get('save')) {
            $recipe = $this->recipe;
            $recipe->set('link', $this->get('link'));
        }

        return parent::save($force);
    }
}
