<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephnan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Config;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
interface ConfigurationInterface
{
    public function all();

    public function has($key);

    public function get($key, $default = null);

    public function set($key, $value);

    public function remove($key);

    public function load();

    public function persist($force = false);
}
