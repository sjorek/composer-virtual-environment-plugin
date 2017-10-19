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

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkConfiguration extends AbstractCommandConfiguration
{
    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::setup()
     */
    protected function setup()
    {
        $recipe = $this->recipe;
        $input = $this->input;
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
            if ($input->getOption('add')) {
                $symlinks = array_merge($recipe->get('link', array()), $symlinks);
            }
        } elseif ($recipe->has('link')) {
            $symlinks = $recipe->get('link');
        }
        $this->set('link', $symlinks);
        $this->set('link-expanded', $this->expandConfig($symlinks));

        return true;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareSave()
     */
    protected function prepareSave(FileConfigurationInterface $recipe)
    {
        $recipe->set('link', $this->get('link'));

        return $recipe;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareLock()
     */
    protected function prepareLock(FileConfigurationInterface $recipe)
    {
        $recipe->set('link', $this->get('link') ?: new \stdClass());
        $recipe->set('link-expanded', $this->get('link-expanded') ?: new \stdClass());

        return $recipe;
    }
}
