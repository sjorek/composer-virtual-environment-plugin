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
     * @param string $candidates
     * @return array
     */
    public static function importConfiguration(array $candidates)
    {
        $candidates = array_map('trim', array_map('strtolower', $candidates));
        $activators = array_map('trim', explode(',', strtolower(static::AVAILABLE_ACTIVATORS)) ?: array());

        // Get a list of valid $activators
        $activators = array_intersect($candidates, $activators);

        // Make the 'activate' shortcut available if needed
        if (in_array('bash', $activators, true) && in_array('zsh', $activators, true)) {
            $activators[] = 'activate';
        }

        // Remove duplicates introduced by user or shortcut addition from above
        $activators = array_unique($activators);

        // Create filenames
        $activators = array_map(
            function($activator) {
                return $activator === 'activate' ? $activator : ('activate.' . $activator);
            },
            $activators
        );

        return $activators;
    }

    /**
     * @param array $activators
     * @return string
     */
    public static function exportConfiguration(array $activators)
    {
        // Restore activator names from filenames - 'activate' will be removed by array_filter below
        // array_filter removes 'activate' fragment left over by the restore from above
        return array_filter(array_map(
            function($activator) {
                return $activator === 'activate' ? '' : pathinfo($activator, PATHINFO_EXTENSION);
            },
            $activators
        ));
    }

    /**
     * @param string $source
     * @param string $target
     * @param array $data
     */
    public function __construct($source, $target, array $data)
    {
        $this->source = $source;
        $this->target = $target;
        $this->data = $data;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param OutputInterface $output
     * @param string $force
     * @return boolean
     */
    public function deploy(OutputInterface $output, $force = false)
    {
        if (file_exists($this->target) || is_link($this->target)) {
            if ($force) {
                if ($this->filesystem->unlink($this->target)) {
                    $output->writeln('Removed existing virtual environment activation script: ' . $this->target);
                } else {
                    $output->writeln('    <error>Could not remove virtual environment activation script:</error> ' . $this->target);
                    return false;
                }
            } else {
                $output->writeln('    <error>Skipped installation of shell activator:</error> file "'.$this->target.'" already exists');
                return false;
            }
        }

        $content = file_get_contents($this->source, false);
        if ($content === false) {
            $output->writeln('    <error>Skipped installation of shell activator:</error> could not read the template file "'.$this->source.'"');
            return false;
        }
        $content = str_replace(
            array_keys($this->data),
            array_values($this->data),
            $content
        );
        $this->filesystem->ensureDirectoryExists(dirname($this->target));
        if (file_put_contents($this->target, $content) === false) {
            $output->writeln('    <error>Skipped installation of shell activator:</error> could not write the shell activator file "'.$this->target.'"');
            return false;
        }
        Silencer::call('chmod', $this->target, 0777 & ~umask());
        $output->writeln('Installed virtual environment activation script: ' . $this->target);

        return true;
    }

    /**
     * @param OutputInterface $output
     * @return boolean
     */
    public function rollback(OutputInterface $output)
    {
        if (file_exists($this->target) || is_link($this->target)) {
            if ($this->filesystem->unlink($this->target)) {
                $output->writeln('Removed virtual environment activation script: ' . $this->target);
                return true;
            } else {
                $output->writeln('Could not remove virtual environment activation script: ' . $this->target);
            }
        } else {
            $output->writeln('Skipped removing virtual environment activation script, as it does not exist: ' . $this->target);
        }
        return false;
    }
}
