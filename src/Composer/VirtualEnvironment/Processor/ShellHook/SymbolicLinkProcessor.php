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

use Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkProcessor extends AbstractProcessor
{
    use SymbolicLinkTrait;

    const PROCESSOR_NAME = 'shell-hook symbolic link';

    /**
     * @param string $hook
     * @param string $name
     * @param string $shell
     * @param string $target
     * @param string $baseDir
     * @param string $shellHookDir
     */
    public function __construct($hook, $name, $shell, $target, $baseDir, $shellHookDir = null)
    {
        parent::__construct($hook, $name, $shell, $target, $baseDir, $shellHookDir);
        // Swap source and target for symlinks
        $target = $this->source;
        $this->source = $this->target;
        $this->target = $target;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::deployHook()
     */
    protected function deployHook(OutputInterface $output, $force)
    {
        return $this->deploySymbolicLink($output, $force);
    }

    /**
     * @param  OutputInterface $output
     * @return bool
     */
    protected function rollbackHook(OutputInterface $output)
    {
        return $this->rollbackSymbolicLink($output);
    }
}
