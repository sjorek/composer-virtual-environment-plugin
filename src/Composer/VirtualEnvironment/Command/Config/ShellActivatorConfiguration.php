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

use Composer\Util\Filesystem;
use Sjorek\Composer\VirtualEnvironment\Config\FileConfigurationInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivatorConfiguration extends AbstractCommandConfiguration
{
    const AVAILABLE_ACTIVATORS = 'bash,csh,fish,zsh';

    /**
     * @param  array $candidates
     * @return array
     */
    public static function validate(array $candidates)
    {
        $candidates = array_map('trim', array_map('strtolower', $candidates));
        $activators = array_map('trim', explode(',', strtolower(static::AVAILABLE_ACTIVATORS)) ?: array());

        if (in_array('detect', $candidates, true) && !empty($_SERVER['SHELL'])) {
            $candidates[] = strtolower(trim(basename($_SERVER['SHELL'])));
        }

        // Get a list of valid $activators
        return array_values(array_unique(array_intersect($candidates, $activators)));
    }

    /**
     * @param  array $activators
     * @return array
     */
    public static function translate(array $activators)
    {
        // Make the 'activate' shortcut available if needed
        if (in_array('bash', $activators, true) && in_array('zsh', $activators, true)) {
            $activators[] = 'activate';
        }

        // Remove duplicates introduced by user or shortcut addition from above
        $activators = array_unique($activators);

        // sort them to get nice order
        sort($activators);

        // Create filenames
        $activators = array_map(
            function ($activator) {
                return $activator === 'activate' ? $activator : ('activate.' . $activator);
            },
            $activators
        );

        return $activators;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::setup()
     */
    protected function setup()
    {
        $recipe = $this->recipe;
        $input = $this->input;
        $filesystem = new Filesystem();

        $this->set(
            'resource-dir',
            $filesystem->normalizePath(__DIR__ . '/../../../../../res/shell-activator')
        );

        $name = '{$name}';
        if ($input->getOption('name')) {
            $name = $input->getOption('name');
        } elseif ($recipe->has('name')) {
            $name = $recipe->get('name', $name);
        }
        $this->set('name', $name);
        $this->set('name-expanded', $this->parseConfig($this->parseManifest($name)));

        $candidates = array('detect'); // = explode(',', static::AVAILABLE_ACTIVATORS);
        if ($input->getArgument('shell')) {
            $candidates = $input->getArgument('shell');
            if ($input->getOption('add')) {
                $candidates = array_merge($recipe->get('shell', array()), $candidates);
            }
        } elseif ($recipe->has('shell')) {
            $candidates = $recipe->get('shell');
        }
        $activators = $this->set('shell', self::validate($candidates));

        $colors = true;
        if ($input->getOption('no-colors')) {
            $colors = false;
        } elseif ($input->getOption('colors')) {
            $colors = true;
        } elseif ($recipe->has('colors')) {
            $colors = $recipe->get('colors', $colors);
        }
        $this->set('colors', $colors);

        // If only has been given, we'll symlink to this activator
        if (count($activators) === 1) {
            $symlinks = array('{$bin-dir}/activate' => 'activate.' . $activators[0]);
            $this->set('shell-link', $symlinks);
            $this->set('shell-link-expanded', $this->expandConfig($symlinks));
        }

        return true;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareSave()
     */
    protected function prepareSave(FileConfigurationInterface $recipe)
    {
        $recipe->set('name', $this->get('name'));
        $recipe->set('shell', $this->get('shell'));
        $recipe->set('colors', $this->get('colors'));

        return $recipe;
    }

    /**
     * {@inheritDoc}
     * @see AbstractCommandConfiguration::prepareLock()
     */
    protected function prepareLock(FileConfigurationInterface $recipe)
    {
        $recipe->set('shell-link', $this->get('shell-link') ?: new \stdClass());
        $recipe->set('shell-link-expanded', $this->get('shell-link-expanded') ?: new \stdClass());

        return $recipe;
    }
}
