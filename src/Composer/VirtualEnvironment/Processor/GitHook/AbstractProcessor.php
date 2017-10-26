<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Processor\GitHook;

use Composer\Util\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractProcessor implements GitHookProcessorInterface
{
    protected $hook;
    protected $target;
    protected $source;
    protected $baseDir;
    protected $gitHookDir;
    protected $filesystem;

    /**
     * @param string $hook
     * @param string $source
     * @param string $baseDir
     * @param string $gitHookDir
     * @param string $shebang
     */
    public function __construct($hook, $source, $baseDir, $gitHookDir = null)
    {
        $this->hook = $hook;
        $this->baseDir = $baseDir;
        $this->gitHookDir = $gitHookDir ?: static::GIT_HOOK_DIR;
        $this->target = $this->gitHookDir . '/' . $hook;
        $this->source = $source;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param  OutputInterface $output
     * @param  bool            $force
     * @return bool
     */
    public function deploy(OutputInterface $output, $force = false)
    {
        if (!in_array($this->hook, static::GIT_HOOKS, true)) {
            $output->writeln(
                sprintf(
                    '<error>Invalid git-hook %s given.</error>',
                    $this->hook
                )
            );

            return false;
        }

        return $this->deployHook($output, $force);
    }

    /**
     * @param  OutputInterface $output
     * @param  bool            $force
     * @return bool
     */
    abstract protected function deployHook(OutputInterface $output, $force);

    /**
     * @param  OutputInterface $output
     * @return bool
     */
    public function rollback(OutputInterface $output)
    {
        if (!in_array($this->hook, static::GIT_HOOKS, true)) {
            $output->writeln(
                sprintf(
                    '<error>Invalid git-hook %s given.</error>',
                    $this->hook
                )
            );

            return false;
        }

        return $this->rollbackHook($output);
    }

    /**
     * @param  OutputInterface $output
     * @return bool
     */
    abstract protected function rollbackHook(OutputInterface $output);
}
