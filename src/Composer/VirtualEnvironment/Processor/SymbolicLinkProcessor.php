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
class SymbolicLinkProcessor implements ProcessorInterface
{
    use SymbolicLinkTrait;

    const PROCESSOR_NAME = 'symbolic link';

    /**
     * @param string $source
     * @param string $target
     * @param string $baseDir
     */
    public function __construct($source, $target, $baseDir)
    {
        $this->source = $source;
        $this->target = $target;
        $this->baseDir = $baseDir;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param  OutputInterface $output
     * @param  bool            $force
     * @return bool
     */
    public function deploy(OutputInterface $output, $force = false)
    {
        return $this->deploySymbolicLink($output, $force);
    }

    /**
     * @param  OutputInterface $output
     * @return bool
     */
    public function rollback(OutputInterface $output)
    {
        return $this->rollbackSymbolicLink($output);
    }
}
