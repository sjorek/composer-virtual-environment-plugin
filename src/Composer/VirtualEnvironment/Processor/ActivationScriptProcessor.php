<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Processor;

use Symfony\Component\Console\Output\OutputInterface;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ActivationScriptProcessor
{
    const AVAILABLE_ACTIVATORS = 'bash,csh,fish,zsh';

    protected $source;
    protected $target;
    protected $basePath;
    protected $data;
    protected $filesystem;

    /**
     * @param  array $candidates
     * @return array
     */
    public static function import(array $candidates)
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
    public static function export(array $activators)
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
     * @param string $source
     * @param string $target
     * @param string $basePath
     * @param array  $data
     */
    public function __construct($source, $target, $basePath, array $data)
    {
        $this->source = $source;
        $this->target = $target;
        $this->data = $data;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param  OutputInterface $output
     * @param  string          $force
     * @return bool
     */
    public function deploy(OutputInterface $output, $force = false)
    {
        $source = $this->source;
        $target = $this->target;
        if (file_exists($target) || is_link($target)) {
            if ($force) {
                try {
                    if ($this->filesystem->unlink($target)) {
                        $output->writeln(
                            sprintf(
                                '<comment>Removed existing shell activation script %s.</comment>',
                                $this->target
                            ),
                            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                        );
                    }
                } catch (\RuntimeException $e) {
                    $output->writeln(
                        sprintf(
                            '<error>Failed to remove the existing shell activation script %s: %s.</error>',
                            $this->target,
                            $e->getMessage()
                        )
                    );

                    return false;
                }
            } else {
                $output->writeln(
                    sprintf(
                        '<error>Shell activation script %s already exists.</error>',
                        $this->target
                    )
                );

                return false;
            }
        }

        if (!file_exists($source)) {
            $output->writeln(
                sprintf(
                    '<error>The shell activation script template %s does not exist.</error>',
                    $this->source
                )
            );

            return false;
        }

        $content = @file_get_contents($source, false);
        if ($content === false) {
            $output->writeln(
                sprintf(
                    '<error>Failed to read the template file %s.</error>',
                    $this->source
                )
            );

            return false;
        }
        $content = str_replace(
            array_keys($this->data),
            array_values($this->data),
            $content
        );
        if (dirname($target)) {
            try {
                $this->filesystem->ensureDirectoryExists(dirname($target));
            } catch (\RuntimeException $e) {
                $output->writeln(
                    sprintf(
                        '<error>Failed to create the target directory %s: %s</error>',
                        dirname($target),
                        $e->getMessage()
                    )
                );

                return false;
            }
        }
        if (@file_put_contents($target, $content) === false) {
            $output->writeln(
                sprintf(
                    '<error>Failed to write the shell activation script %s.</error>',
                    $this->target
                )
            );

            return false;
        }
        Silencer::call('chmod', $target, 0777 & ~umask());

        $output->writeln(
            sprintf(
                '<comment>Installed shell activation script %s.</comment>',
                $this->target
            ),
            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
        );

        return true;
    }

    /**
     * @param  OutputInterface $output
     * @param  bool            $force
     * @return bool
     */
    public function rollback(OutputInterface $output)
    {
        $target = $this->target;
        if (file_exists($target)) {
            // For existing symlinks
            if (is_link($target)) {
                $output->writeln(
                    sprintf(
                        '<error>Refused to remove the shell activation script %s, as it is a symbolic link.</error>',
                        $this->target
                    )
                );
            } else {
                try {
                    if ($this->filesystem->unlink($target)) {
                        $output->writeln(
                            sprintf(
                                '<comment>Removed shell activation script %s.</comment>',
                                $this->target
                            ),
                            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                        );

                        return true;
                    }
                } catch (\RuntimeException $e) {
                    $output->writeln(
                        sprintf(
                            '<error>Failed to remove the shell activation script %s: %s</error>',
                            $this->target,
                            $e->getMessage()
                        )
                    );

                    return false;
                }
            }
            // For dangling symlinks
        } elseif (is_link($target)) {
            $output->writeln(
                sprintf(
                    '<error>Refused to remove the shell activation script %s, as it is a dangling symbolic link.</error>',
                    $this->target
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    '<comment>Skipped removing the shell activation script %s, as it does not exist.</comment>',
                    $this->target
                ),
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );

            return true;
        }

        return false;
    }
}
