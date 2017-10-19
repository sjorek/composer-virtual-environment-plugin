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
trait ExecutableFromTemplateTrait
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
     * @param  string          $force
     * @return bool
     */
    protected function deployTemplate(OutputInterface $output, $force)
    {
        $content = $this->fetchTemplate($output, $force);
        if ($content === false) {
            $output->writeln(
                sprintf(
                    '<error>Failed to fetch the %s template %s.</error>',
                    static::PROCESSOR_NAME,
                    $this->source
                )
            );

            return false;
        }

        $target = $this->target;
        if (file_exists($target) || is_link($target)) {
            if ($force) {
                try {
                    if ($this->filesystem->unlink($target)) {
                        $output->writeln(
                            sprintf(
                                '<comment>Removed existing %s %s.</comment>',
                                static::PROCESSOR_NAME,
                                $this->target
                            ),
                            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                        );
                    }
                } catch (\RuntimeException $e) {
                    $output->writeln(
                        sprintf(
                            '<error>Failed to remove the existing %s %s: %s.</error>',
                            static::PROCESSOR_NAME,
                            $this->target,
                            $e->getMessage()
                        )
                    );

                    return false;
                }
            } else {
                $output->writeln(
                    sprintf(
                        '<error>The %s %s already exists.</error>',
                        static::PROCESSOR_NAME,
                        $this->target
                    )
                );

                return false;
            }
        }

        if (dirname($target)) {
            try {
                $this->filesystem->ensureDirectoryExists(dirname($target));
            } catch (\RuntimeException $e) {
                $output->writeln(
                    sprintf(
                        '<error>Failed to create the %s target directory %s: %s</error>',
                        static::PROCESSOR_NAME,
                        dirname($target),
                        $e->getMessage()
                    )
                );

                return false;
            }
        }

        if (@file_put_contents($target, $this->renderTemplate($content, $output, $force)) === false) {
            $output->writeln(
                sprintf(
                    '<error>Failed to write the %s %s.</error>',
                    static::PROCESSOR_NAME,
                    $this->target
                )
            );

            return false;
        }
        Silencer::call('chmod', $target, 0777 & ~umask());

        $output->writeln(
            sprintf(
                '<comment>Installed %s %s.</comment>',
                static::PROCESSOR_NAME,
                $this->target
            ),
            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
        );

        return true;
    }

    /**
     * @param  OutputInterface $output
     * @param  string          $force
     * @return string|bool
     */
    protected function fetchTemplate(OutputInterface $output, $force = false)
    {
        $source = $this->source;
        if (!file_exists($source)) {
            $output->writeln(
                sprintf(
                    '<error>The %s template %s does not exist.</error>',
                    static::PROCESSOR_NAME,
                    $this->source
                )
            );

            return false;
        }

        return @file_get_contents($source, false);
    }

    /**
     * @param  string          $content
     * @param  OutputInterface $output
     * @param  string          $force
     * @return string|bool
     */
    protected function renderTemplate($content, OutputInterface $output, $force = false)
    {
        return $content;
    }

    /**
     * @param  OutputInterface $output
     * @param  bool            $force
     * @return bool
     */
    protected function rollbackTemplate(OutputInterface $output)
    {
        $target = $this->target;
        if (file_exists($target)) {
            // For existing symlinks
            if (is_link($target)) {
                $output->writeln(
                    sprintf(
                        '<error>Refused to remove the %s %s, as it is a symbolic link.</error>',
                        static::PROCESSOR_NAME,
                        $this->target
                    )
                );

                return false;
            } else {
                try {
                    if ($this->filesystem->unlink($target)) {
                        $output->writeln(
                            sprintf(
                                '<comment>Removed %s %s.</comment>',
                                static::PROCESSOR_NAME,
                                $this->target
                            ),
                            OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                        );

                        return true;
                    }
                } catch (\RuntimeException $e) {
                    $output->writeln(
                        sprintf(
                            '<error>Failed to remove the %s %s: %s</error>',
                            static::PROCESSOR_NAME,
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
                    '<error>Refused to remove the %s %s, as it is a dangling symbolic link.</error>',
                    static::PROCESSOR_NAME,
                    $this->target
                )
            );

            return false;
        }

        $output->writeln(
            sprintf(
                '<error>Skipped removing the %s %s, as it does not exist.</error>',
                static::PROCESSOR_NAME,
                $this->target
            )
        );

        return true;
    }
}
