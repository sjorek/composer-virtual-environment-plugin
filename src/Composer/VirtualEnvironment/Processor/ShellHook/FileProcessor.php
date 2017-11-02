<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Processor\ShellHook;

use Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class FileProcessor extends AbstractProcessor
{
    use ExecutableFromTemplateTrait;

    const PROCESSOR_NAME = 'shell-hook file';

    /**
     * @param string $hook
     * @param string $name
     * @param string $shell
     * @param string $file
     * @param string $baseDir
     * @param string $shellHookDir
     */
    public function __construct($hook, $name, $shell, $file, $baseDir, $shellHookDir = null)
    {
        parent::__construct($hook, $name, $shell, $file, $baseDir, $shellHookDir);
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::deployHook()
     */
    protected function deployHook(OutputInterface $output, $force)
    {
        return $this->deployTemplate($output, $force);
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::rollbackHook()
     */
    protected function rollbackHook(OutputInterface $output)
    {
        return $this->rollbackTemplate($output);
    }
}
