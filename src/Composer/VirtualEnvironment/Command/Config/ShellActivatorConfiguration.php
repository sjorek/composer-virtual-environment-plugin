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

use Composer\Util\Filesystem;
use Sjorek\Composer\VirtualEnvironment\Config\FileConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Config\ShellConstants;
use Symfony\Component\Console\Output\OutputInterface;

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
        $candidates = array_unique(
            array_map(
                'trim',
                array_map(
                    function ($candidate) {
                        // Remove potential '.exe' suffix for windows users … :-P
                        return basename(strtolower($candidate), '.exe');
                    },
                    $candidates
                )
            )
        );

        $amount = count($candidates);
        if (in_array('detect', $candidates, true)) {
            if (getenv('SHELL') !== false) {
                // Remove potential '.exe' suffix for windows users … :-P
                $candidates[] = strtolower(trim(basename(getenv('SHELL'), '.exe')));
            } else {
                $amount -= 1;
            }
        }

        $available = array_merge(
            explode(',', static::SHELLS_POSIX),
            explode(',', static::SHELLS_NT)
        );

        // Get a list of valid candidates
        $candidates = array_values(array_unique(array_intersect($candidates, $available)));

        // Return false if invalid shells where given
        return count($candidates) === $amount ? $candidates : false;
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
                if ($activator === 'cmd') {
                    $filenames = array('activate.bat', 'deactivate.bat');
                    $shell = 'cmd.exe';
                } elseif($activator === 'powershell') {
                    $filenames = array('Activate.ps1');
                    $shell = 'powershell.exe';
                } else {
                    $filenames = array('activate.' . $activator);
                    // Remove potential '.exe' suffix for windows users … :-P
                    if (getenv('SHELL') !== false && basename(getenv('SHELL'), '.exe') === $activator) {
                        $shell = escapeshellcmd(getenv('SHELL'));
                    } elseif ($activator === 'sh') {
                        $shell = escapeshellcmd($shebangSh);
                    } else {
                        $shell = sprintf($shebangEnv, escapeshellcmd($activator));
                    }
                }

                return array(
                    'filenames' => $filenames,
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
        $output = $this->output;
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

        $candidates = array('detect'); // = explode(',', static::SHELLS_POSIX);
        if ($input->getArgument('shell')) {
            $candidates = $input->getArgument('shell');
            if ($input->getOption('add')) {
                $candidates = array_merge($recipe->get('shell', array()), $candidates);
            }
        } elseif ($recipe->has('shell')) {
            $candidates = $recipe->get('shell');
        }

        $candidates = self::validateActivators($candidates);
        if ($candidates === false) {
            $output->writeln(
                sprintf(
                    '<error>Invalid shell given or detected. Supported shells are: %s (posix) and %s (nt)</error>',
                    self::SHELLS_POSIX,
                    self::SHELLS_NT
                ),
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );

            return false;
        } else {
            $activators = $this->set('shell', $candidates);
            $activators = $this->set('shell-expanded', self::expandActivators($activators));
        }

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
        // If only one posix shell has been given, we'll symlink to this activator
        $posix = array_intersect(array_keys($activators), explode(',', self::SHELLS_POSIX));
        if (count($posix) === 1) {
            $symlink = $activators[reset($posix)];
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
        $recipe->set('name', $this->get('name'));
        $recipe->set('shell', $this->get('shell'));
        $recipe->set('colors', $this->get('colors'));
        $recipe->set('shell-link', $this->get('shell-link') ?: new \stdClass());
        $recipe->set('shell-link-expanded', $this->get('shell-link-expanded') ?: new \stdClass());

        return $recipe;
    }
}
