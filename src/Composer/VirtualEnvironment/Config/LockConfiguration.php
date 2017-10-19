<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Config;

use Composer\Composer;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class LockConfiguration extends FileConfiguration
{
    const INFO = array(
        'virtual environment configuration lock file',
        'generated by the composer-virtual-environment-plugin',
    );

    protected $blacklist = array(
        'info', 'add', 'remove', 'load', 'save', 'lock', 'force', 'global', 'local', 'config',
    );

    /**
     * @param Composer $composer
     * @param string   $file
     */
    public function __construct(Composer $composer, $file)
    {
        if (strpos($file, 'php://') === false) {
            $extension = pathinfo($file, PATHINFO_EXTENSION) ?: 'json';
            $file = dirname($file) . DIRECTORY_SEPARATOR . basename($file, '.' . $extension) . '.lock';
        }
        parent::__construct($composer, $file);
    }
}