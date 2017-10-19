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

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
interface ConfigurationInterface
{
    /**
     * @return array
     */
    public function all();

    /**
     * @param  string $key
     * @return bool
     */
    public function has($key);

    /**
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function set($key, $value);

    /**
     * @param  string                 $key
     * @return ConfigurationInterface
     */
    public function remove($key);

    /**
     * @param  string                 $key
     * @return ConfigurationInterface
     */
    public function import(ConfigurationInterface $config);

    /**
     * @param  string                 $key
     * @return ConfigurationInterface
     */
    public function merge(ConfigurationInterface $config);

    /**
     * @return array
     */
    public function export();
}
