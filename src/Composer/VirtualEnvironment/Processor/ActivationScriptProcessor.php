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
    protected $data;
    protected $filesystem;

    /**
     * @param  string $candidates
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
     * @param  string $activators
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
     * @param array  $data
     */
    public function __construct($source, $target, array $data)
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
                if ($this->filesystem->unlink($target)) {
                    $output->writeln(
                        sprintf(
                            '<comment>Removed existing shell activation script %s.</comment>',
                            $target
                        )
                    );
                } else {
                    $output->writeln(
                        sprintf(
                            '<error>Failed to remove the shell activation script %s.</error>',
                            $target
                        )
                    );

                    return false;
                }
            } else {
                $output->writeln(
                    sprintf(
                        '<error>Skipped installation of the shell activation script %s, as the file already exists.</error>',
                        $target
                    )
                );

                return false;
            }
        }

        $content = file_get_contents($source, false);
        if ($content === false) {
            $output->writeln(
                sprintf(
                    '<error>Failed to read the template file %s for shell activation script %s.</error>',
                    $source,
                    $target
                )
            );

            return false;
        }
        $content = str_replace(
            array_keys($this->data),
            array_values($this->data),
            $content
        );
        if (strpos($target, '/') !== false) {
            $this->filesystem->ensureDirectoryExists(dirname($target));
        }
        if (file_put_contents($target, $content) === false) {
            $output->writeln(
                sprintf(
                    '<error>Failed to write the shell activation script %s.</error>',
                    $target
                )
            );

            return false;
        }
        Silencer::call('chmod', $target, 0777 & ~umask());

        $output->writeln(
            sprintf(
                '<comment>Installed shell activation script %s.</comment>',
                $target
            )
        );

        return true;
    }

    /**
     * @param  OutputInterface $output
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
                        $target
                    )
                );
            } else {
                if ($this->filesystem->unlink($target)) {
                    $output->writeln(
                        sprintf(
                            '<comment>Removed shell activation script %s.</comment>',
                            $target
                        )
                    );

                    return true;
                } else {
                    $output->writeln(
                        sprintf(
                            '<error>Failed to remove the shell activation script %s.</error>',
                            $target
                        )
                    );
                }
            }
            // For dangeling symlinks
        } elseif (is_link($target)) {
            $output->writeln(
                sprintf(
                    '<error>Refused to remove the shell activation script %s, as it is a dangeling symbolic link.</error>',
                    $target
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    '<comment>Skipped removing the shell activation script %s, as it does not exist.</comment>',
                    $target
                )
            );

            return true;
        }

        return false;
    }
}
