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

use Composer\Util\Filesystem;
use Sjorek\Composer\VirtualEnvironment\Config\ShellConstants;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractProcessor implements ShellHookProcessorInterface, ShellConstants
{
    protected $hook;
    protected $name;
    protected $shell;
    protected $shellHookDir;

    /**
     * @param string $hook
     * @param string $name
     * @param string $shell
     * @param string $source
     * @param string $baseDir
     * @param string $shellHookDir
     */
    public function __construct($hook, $name, $shell, $source, $baseDir, $shellHookDir = null)
    {
        $this->hook = $hook;
        $this->name = $name;
        $this->shell = $shell ?: self::SHEBANG_SH;
        $this->source = $source;
        $this->baseDir = $baseDir;
        $this->shellHookDir = $shellHookDir ?: static::SHELL_HOOK_DIR;
        $this->target = sprintf(
            '%s/%s.d/%s.%s',
            $this->shellHookDir,
            $hook,
            $name,
            basename($this->shell)
        );
        $this->filesystem = new Filesystem();
    }

    /**
     * @param  OutputInterface $output
     * @param  bool            $force
     * @return bool
     */
    public function deploy(OutputInterface $output, $force = false)
    {
        $SHELL_HOOKS = explode(',', static::SHELL_HOOKS);
        if (!in_array($this->hook, $SHELL_HOOKS, true)) {
            $output->writeln(
                sprintf(
                    '<error>Invalid shell-hook %s given.</error>',
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
        $SHELL_HOOKS = explode(',', static::SHELL_HOOKS);
        if (!in_array($this->hook, $SHELL_HOOKS, true)) {
            $output->writeln(
                sprintf(
                    '<error>Invalid shell-hook %s given.</error>',
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
