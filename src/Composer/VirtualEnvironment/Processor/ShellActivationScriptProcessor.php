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
class ShellActivationScriptProcessor implements ProcessorInterface
{
    use ExecutableFromTemplateTrait;

    const PROCESSOR_NAME = 'shell activation script';

    protected $data;

    /**
     * @param string $source
     * @param string $target
     * @param string $baseDir
     * @param array  $data
     */
    public function __construct($source, $target, $baseDir, array $data)
    {
        $this->source = $source;
        $this->target = $target;
        $this->baseDir = $baseDir;
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
        return $this->deployTemplate($output, $force);
    }

    /**
     * @param  string          $content
     * @param  OutputInterface $output
     * @param  string          $force
     * @return bool
     */
    protected function renderTemplate($content, OutputInterface $output, $force)
    {
        return str_replace(
            array_keys($this->data),
            array_values($this->data),
            $content
        );
    }

    /**
     * @param  OutputInterface $output
     * @param  string          $force
     * @return bool
     */
    public function rollback(OutputInterface $output)
    {
        return $this->rollbackTemplate($output);
    }
}
