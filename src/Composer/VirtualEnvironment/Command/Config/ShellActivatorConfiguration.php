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
use Sjorek\Composer\VirtualEnvironment\Config\ShellConstants;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivatorConfiguration extends AbstractCommandConfiguration implements ShellConstants
{
    /**
     * @param  array $candidates
     * @return array
     */
    public static function validateActivators(array $candidates)
    {
        $candidates = array_map('trim', array_map('strtolower', $candidates));

        if (in_array('detect', $candidates, true)) {
            if (!empty($_SERVER['SHELL'])) {
                $candidates[] = strtolower(trim(basename($_SERVER['SHELL'])));
            } elseif (!empty($_ENV['SHELL'])) {
                $candidates[] = strtolower(trim(basename($_ENV['SHELL'])));
            }
        }

        // Get a list of valid $activators
        return array_values(array_unique(array_intersect($candidates, static::SHELLS)));
    }

    /**
     * @param  array $activators
     * @return array
     */
    public static function expandActivators(array $activators)
    {

        // Remove duplicates introduced by user
        $activators = array_unique($activators);

        // make array accessible by key
        $activators = array_combine($activators, $activators);

        // Make the 'activate.sh' shortcut available if needed
        if (isset($activators['bash']) && isset($activators['zsh']) && !isset($activators['sh'])) {
            $activators['sh'] = 'sh';
        }

        $shebangSh = static::SHEBANG_SH;
        $shebangEnv = static::SHEBANG_ENV;

        // Create activator configuration
        $activators = array_map(
            function ($activator) use ($shebangSh, $shebangEnv) {
                $filename = 'activate.' . $activator;
                if (isset($_SERVER['SHELL']) && basename($_SERVER['SHELL']) === $activator) {
                    $shell = escapeshellcmd($_SERVER['SHELL']);
                } elseif (isset($_ENV['SHELL']) && basename($_ENV['SHELL']) === $activator) {
                    $shell = ecapeshellcmd($_ENV['SHELL']);
                } elseif ($activator === 'sh') {
                    $shell = escapeshellcmd($shebangSh);
                } else {
                    $shell = sprintf($shebangEnv, escapeshellcmd($activator));
                }

                return array(
                    'filename' => $filename,
                    'shell' => $shell,
                );
            },
            $activators
        );

        // sort them to get nice order
        ksort($activators);

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
        $this->set('name-expanded', $this->parseExpansion($name));

        $candidates = array('detect'); // = explode(',', static::SHELLS);
        if ($input->getArgument('shell')) {
            $candidates = $input->getArgument('shell');
            if ($input->getOption('add')) {
                $candidates = array_merge($recipe->get('shell', array()), $candidates);
            }
        } elseif ($recipe->has('shell')) {
            $candidates = $recipe->get('shell');
        }
        $activators = $this->set('shell', self::validateActivators($candidates));
        $activators = $this->set('shell-expanded', self::expandActivators($activators));

        $colors = true;
        if ($input->getOption('no-colors')) {
            $colors = false;
        } elseif ($input->getOption('colors')) {
            $colors = true;
        } elseif ($recipe->has('colors')) {
            $colors = $recipe->get('colors', $colors);
        }
        $this->set('colors', $colors);

        $symlink = null;
        // If only has been given, we'll symlink to this activator
        if (count($activators) === 1) {
            $symlink = reset($activators);
        } elseif (isset($activators['sh'])) {
            $symlink = $activators['sh'];
        }
        if ($symlink !== null) {
            $symlinks = array('{$bin-dir}/activate' => $symlink['filename']);
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
