<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command\Config;

use Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
interface CommandConfigurationInterface extends ConfigurationInterface
{
    /**
     * @return bool
     */
    public function load();

    /**
     * @param  bool $force
     * @return bool
     */
    public function save($force = false);

    /**
     * @param $load
     * @return void
     */
    public function lock($load = false);
}
