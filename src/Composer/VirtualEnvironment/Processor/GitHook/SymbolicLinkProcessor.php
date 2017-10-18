<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Processor\GitHook;

use Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkProcessor extends AbstractProcessor
{
    use SymbolicLinkTrait;

    const PROCESSOR_NAME = 'git-hook symbolic link';

    /**
     * @param string $name
     * @param string $target
     * @param string $baseDir
     * @param string $gitHookDir
     */
    public function __construct($name, $target, $baseDir, $gitHookDir = null)
    {
        parent::__construct($name, $target, $baseDir, $gitHookDir);
        // Swap source and target for symlinks
        $target = $this->source;
        $this->source = $this->target;
        $this->target = $target;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::deployHook()
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
