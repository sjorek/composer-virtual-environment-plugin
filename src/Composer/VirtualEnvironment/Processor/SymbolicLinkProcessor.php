<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephnan.jorek@gmail.com>
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
        if ($this->source === $this->target) {
            $output->writeln('    <error>Skipped creation of symbolic link:</error> source and target are the same '.$this->target.' -> ' . $this->source);

            return false;
        }
        if (file_exists($this->target) || is_link($this->target)) {
            if ($force) {
                if ($this->filesystem->unlink($this->target)) {
                    $output->writeln('Removed existing symbolic link: ' . $this->target);
                } else {
                    $output->writeln('    <error>Could not remove existing symbolic link:</error> ' . $this->target);

                    return false;
                }
            } else {
                $output->writeln('    <error>Skipped creation of symbolic link:</error> file "'.$this->target.'" already exists');

                return false;
            }
        }
        if (!(file_exists($this->source) || is_link($this->source))) {
            $output->writeln('    <error>Skipped creation of symbolic link:</error> For "'.$this->target.'" the target "' . $this->source . '" does not exist');

            return false;
        }
        $this->filesystem->ensureDirectoryExists(dirname($this->target));
        if (!$this->filesystem->relativeSymlink($this->source, $this->target)) {
            $output->writeln('    <error>Creation of symbolic link failed:</error> "'.$this->target.'" -> "' . $this->source . '"');

            return false;
        }
        $output->writeln('Installed virtual environment symlink: ' . $this->target .' -> ' . $this->source);

        return true;
    }

    public function rollback(OutputInterface $output)
    {
        if (file_exists($this->target) || is_link($this->target)) {
            if ($this->filesystem->unlink($this->target)) {
                $output->writeln('Removed virtual environment symbolic link: ' . $this->target);

                return true;
            } else {
                $output->writeln('Could not remove virtual environment symbolic link: ' . $this->target);
            }
        } else {
            $output->writeln('Skipped removing virtual environment symbolic link, as it does not exist: ' . $this->target);
        }

        return false;
    }
}
