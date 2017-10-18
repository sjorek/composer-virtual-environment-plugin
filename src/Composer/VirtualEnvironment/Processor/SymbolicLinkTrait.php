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

use Composer\Util\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
trait SymbolicLinkTrait
{
    protected $source;
    protected $target;
    protected $baseDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param  OutputInterface $output
     * @param  bool            $force
     * @return bool
     */
    protected function deploySymbolicLink(OutputInterface $output, $force)
    {
        $source = $this->source;
        if (!$this->filesystem->isAbsolutePath($source)) {
            $source = $this->baseDir . '/' . $source;
        }
        $target = $this->target;
        if (!$this->filesystem->isAbsolutePath($target)) {
            $target = dirname($source) . '/' . $target;
        }

        if ($source === $target) {
            $output->writeln(
                sprintf(
                    '<error>Skipped creation of %s, as source %s and target %s are the same.</error>',
                    static::PROCESSOR_NAME,
                    $this->source,
                    $this->target
                )
            );

            return false;
        }
        if (file_exists($source) || is_link($source)) {
            if ($force) {
                try {
                    if ($this->filesystem->unlink($source)) {
                        $output->writeln(
                            sprintf(
                                '<comment>Removed existing file for %s %s.</comment>',
                                static::PROCESSOR_NAME,
                                $this->source
                            ),
                            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                        );
                    }
                } catch (\RuntimeException $e) {
                    $output->writeln(
                        sprintf(
                            '<error>Could not remove existing %s %s: %s</error>',
                            static::PROCESSOR_NAME,
                            $this->source,
                            $e->getMessage()
                        )
                    );

                    return false;
                }
            } else {
                $output->writeln(
                    sprintf(
                        '<error>Skipped creation of %s, as the source %s already exists.</error>',
                        static::PROCESSOR_NAME,
                        $this->source
                    )
                );

                return false;
            }
        }
        if (!(file_exists($target) || is_link($target))) {
            $output->writeln(
                sprintf(
                    '<error>Skipped creation of %s, as the target %s does not exist.</error>',
                    static::PROCESSOR_NAME,
                    $this->target
                )
            );

            return false;
        }
        if (dirname($source)) {
            try {
                $this->filesystem->ensureDirectoryExists(dirname($source));
            } catch (\RuntimeException $e) {
                $output->writeln(
                    sprintf(
                        '<error>Failed to create the %s directory %s: %s</error>',
                        static::PROCESSOR_NAME,
                        dirname($source),
                        $e->getMessage()
                    )
                );

                return false;
            }
        }
        // special treatment for relative symlinks in the same directory,
        // because composer's implementation uses a leading dot (./...)
        if (strpos($this->target, '/') === false && symlink($this->target, $source)) {
            $output->writeln(
                sprintf(
                    '<comment>Installed %s %s to target %s.</comment>',
                    static::PROCESSOR_NAME,
                    $this->source,
                    $this->target
                ),
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );

            return true;
        } elseif (strpos($this->target, '/') !== false && $this->filesystem->relativeSymlink($target, $source)) {
            $output->writeln(
                sprintf(
                    '<comment>Installed %s %s to target %s.</comment>',
                    static::PROCESSOR_NAME,
                    $this->source,
                    $this->target
                ),
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );

            return true;
        } else {
            $output->writeln(
                sprintf(
                    '<error>Creation of %s failed for source %s and target %s.</error>',
                    static::PROCESSOR_NAME,
                    $this->source,
                    $this->target
                )
            );
        }

        return false;
    }

    /**
     * @param  OutputInterface $output
     * @return bool
     */
    protected function rollbackSymbolicLink(OutputInterface $output)
    {
        $source = $this->source;
        if (!$this->filesystem->isAbsolutePath($source)) {
            $source = $this->baseDir . '/' . $source;
        }
        // Attention: Dangling symlinks return false for is_link(), hence we have to use file_exists()!
        if (file_exists($source) || is_link($source)) {
            try {
                if ($this->filesystem->unlink($source)) {
                    $output->writeln(
                        sprintf(
                            '<comment>Removed %s %s.</comment>',
                            static::PROCESSOR_NAME,
                            $this->source
                        ),
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );

                    return true;
                }
            } catch (\RuntimeException $e) {
                $output->writeln(
                    sprintf(
                        '<error>Could not remove %s %s: %s</error>',
                        static::PROCESSOR_NAME,
                        $this->source,
                        $e->getMessage()
                    )
                );

                return false;
            }
        } else {
            $output->writeln(
                sprintf(
                    '<comment>Skipped removing %s, as %s does not exist.</comment>',
                    static::PROCESSOR_NAME,
                    $this->source
                ),
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );

            return true;
        }

        return false;
    }
}
