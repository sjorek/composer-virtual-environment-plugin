<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Config;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
interface ShellConstants
{
    const SHELLS_POSIX = 'bash,csh,fish,zsh';
    const SHELLS_NT = 'cmd,powershell';
    const SHEBANG_SH = '/bin/sh';
    const SHEBANG_ENV = '/usr/bin/env %s';
}
