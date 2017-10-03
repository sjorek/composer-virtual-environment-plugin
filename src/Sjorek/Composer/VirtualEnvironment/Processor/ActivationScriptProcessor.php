<?php
namespace Sjorek\Composer\VirtualEnvironment\Processor;

use Symfony\Component\Console\Output\OutputInterface;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;

/**
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ActivationScriptProcessor
{
    protected $source;
    protected $target;
    protected $data;
    protected $filesystem;

    public function __construct($source, $target, array $data)
    {
        $this->source = $source;
        $this->target = $target;
        $this->data = $data;
        $this->filesystem = new Filesystem();
    }

    public function deploy(OutputInterface $output, $force = false)
    {
        if (file_exists($this->target) && !$force) {
            $output->writeln('    <warning>Skipped installation of bin '.$this->target.': file already exists</warning>');
            return false;
        }

        $content = file_get_contents($this->source, false);
        $content = str_replace(
            array_keys($this->data),
            array_values($this->data),
            $content
        );
        $this->filesystem->ensureDirectoryExists(dirname($this->target));
        file_put_contents($this->target, $content);
        Silencer::call('chmod', $this->target, 0777 & ~umask());
        $output->writeln('Installed virtual environment activation script: ' . $this->target);
        return true;
    }

    public function rollback($output)
    {
        if (file_exists($this->target)) {
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

