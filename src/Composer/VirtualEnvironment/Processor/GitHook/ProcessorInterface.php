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

use Sjorek\Composer\VirtualEnvironment\Processor;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
interface ProcessorInterface extends Processor\ProcessorInterface
{
    const GIT_HOOK_DIR = '.git/hooks';

    const GIT_HOOKS = array(
        'applypatch-msg',
        'commit-msg',
        'post-applypatch',
        'post-checkout',
        'post-commit',
        'post-merge',
        'post-receive',
        'post-rewrite',
        'post-update',
        'pre-applypatch',
        'pre-auto-gc',
        'pre-commit',
        'pre-push',
        'pre-rebase',
        'pre-receive',
        'prepare-commit-msg',
        'push-to-checkout',
        'update',
    );
}
