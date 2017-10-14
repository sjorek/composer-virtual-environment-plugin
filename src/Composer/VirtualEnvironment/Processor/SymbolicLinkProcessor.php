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

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkProcessor
{
    protected $source;
    protected $target;
    protected $filesystem;

    public function __construct($source, $target)
    {
        $this->source = $source;
        $this->target = $target;
        $this->filesystem = new Filesystem();
    }

    public function deploy(OutputInterface $output, $force = false)
    {
        $source = $this->source;
        $target = $this->target;
        if (strpos($target, '/') === false && strpos($source, '/') !== false) {
            $target = dirname($source) . '/' . $target;
        }

        if ($source === $target) {
            $output->writeln(
                sprintf(
                    '<error>Skipped creation of symbolic link, as source %s and target %s are the same.</error>',
                    $source,
                    $target
                )
            );

            return false;
        }
        if (file_exists($source) || is_link($source)) {
            if ($force) {
                if ($this->filesystem->unlink($source)) {
                    $output->writeln(
                        sprintf(
                            '<comment>Removed existing symbolic link %s.</comment>',
                            $source
                        )
                    );
                } else {
                    $output->writeln(
                        sprintf(
                            '<error>Could not remove existing symbolic link %s.</error>',
                            $source
                        )
                    );

                    return false;
                }
            } else {
                $output->writeln(
                    sprintf(
                        '<error>Skipped creation of symbolic link, as the source %s already exists.</error>',
                        $source
                    )
                );

                return false;
            }
        }
        if (!(file_exists($target) || is_link($target))) {
            $output->writeln(
                sprintf(
                    '<error>Skipped creation of symbolic link, as the target %s does not exist.</error>',
                    $target
                )
            );

            return false;
        }
        if (strpos($source, '/') !== false) {
            $this->filesystem->ensureDirectoryExists(dirname($source));
        }
        // Attention: we deliberately use $this->target instead of $target to allow relative targets!
        if ($this->filesystem->relativeSymlink($this->target, $source)) {
            $output->writeln(
                sprintf(
                    '<comment>Installed symbolic link link from source %s to target %s.</comment>',
                    $source,
                    $target
                )
            );

            return true;
        } else {
            $output->writeln(
                sprintf(
                    '<error>Creation of symbolic link failed for source %s and target %s.</error>',
                    $source,
                    $target
                )
            );
        }

        return false;
    }

    public function rollback(OutputInterface $output)
    {
        $source = $this->source;
        // Attention: Dangling symlinks return false for is_link(), hence we have to use file_exists()!
        if (file_exists($source) || is_link($source)) {
            if ($this->filesystem->unlink($source)) {
                $output->writeln(
                    sprintf(
                        '<comment>Removed symbolic link %s.</comment>',
                        $source
                    )
                );

                return true;
            } else {
                $output->writeln(
                    sprintf(
                        '<error>Could not remove symbolic link %s.</error>',
                        $source
                    )
                );
            }
        } else {
            $output->writeln(
                sprintf(
                    '<comment>Skipped removing symbolic link, as %s does not exist.</comment>',
                    $source
                )
            );

            return true;
        }

        return false;
    }
}
