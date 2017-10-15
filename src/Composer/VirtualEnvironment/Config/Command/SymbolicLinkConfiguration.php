<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Config\Command;

use Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkConfiguration extends AbstractCommandConfiguration
{
    protected function setUp(ConfigurationInterface $recipe)
    {
        $input = $this->input;
        $symlinks = array();
        if ($input->getOption('link')) {
            foreach ($input->getOption('link') as $link) {
                list($source, $target) = explode(PATH_SEPARATOR, $link, 2);
                $symlinks[$source] = $target;
            }
        } elseif ($recipe->has('link')) {
            $symlinks = $recipe->get('link');
        }
        $symlinks = array_map('realpath', $symlinks);
        $this->set('link', $symlinks);
    }

    protected function tearDown(ConfigurationInterface $recipe)
    {
        $recipe->set('link', $this->get('link'));
    }
}
