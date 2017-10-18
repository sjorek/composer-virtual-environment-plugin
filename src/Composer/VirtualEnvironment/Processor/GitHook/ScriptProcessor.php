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

use Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ScriptProcessor extends AbstractProcessor
{
    use ExecutableFromTemplateTrait;

    const PROCESSOR_NAME = 'git-hook script';
    const DEFAULT_SHEBANG = "/bin/sh";

    protected $shebang;

    /**
     * @param string      $name
     * @param string      $script
     * @param string      $baseDir
     * @param string      $gitHookDir
     * @param string|bool $shebang
     */
    public function __construct($name, $script, $baseDir, $gitHookDir = null, $shebang = null)
    {
        parent::__construct($name, $script, $baseDir, $gitHookDir);
        $this->shebang = ($shebang === null || $shebang === true) ? static::SHEBANG : $shebang;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::deployHook()
     */
    protected function deployHook(OutputInterface $output, $force)
    {
        return $this->deployTemplate($output, $force);
    }

    /**
     * @param OutputInterface $output
     * @param bool            $force
     */
    protected function fetchTemplate(OutputInterface $output, $force)
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::deployHook()
     */
    protected function rollbackHook(OutputInterface $output, $force)
    {
        return $this->rollbackTemplate($output, $force);
    }
}
