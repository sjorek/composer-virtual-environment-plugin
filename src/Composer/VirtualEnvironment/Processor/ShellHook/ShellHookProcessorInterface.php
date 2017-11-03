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

use Sjorek\Composer\VirtualEnvironment\Processor\ProcessorInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
interface ShellHookProcessorInterface extends ProcessorInterface
{
    const SHELL_HOOK_DIR = '.composer-venv/shell';
    const SHELL_HOOKS = 'post-activate,post-deactivate,pre-activate,pre-deactivate';
}
