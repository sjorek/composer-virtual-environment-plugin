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

use Composer\Composer;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractConfiguration implements ConfigurationInterface
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $dirty;

    /**
     * @var array
     */
    protected $blacklist = array();

    /**
     * @param Composer $composer
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
        $this->data = array();
        $this->dirty = false;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface::all()
     */
    public function all()
    {
        return array_keys($this->data);
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface::has()
     */
    public function has($key)
    {
        if (in_array($key, $this->blacklist, true)) {
            return false;
        }

        return array_key_exists($key, $this->data);
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface::get()
     */
    public function get($key, $default = null)
    {
        if (in_array($key, $this->blacklist, true)) {
            return $default;
        }

        return $this->has($key) ? $this->data[$key] : $default;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface::set()
     */
    public function set($key, $value)
    {
        if (in_array($key, $this->blacklist, true)) {
            return;
        }
        $this->dirty = $this->dirty || ($this->get($key) !== $value);
        $this->data[$key] = $value;

        return $value;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface::remove()
     */
    public function remove($key)
    {
        if (in_array($key, $this->blacklist, true)) {
            return;
        }
        if ($this->has($key)) {
            unset($this->data[$key]);
            $this->dirty = true;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface::import()
     */
    public function import(ConfigurationInterface $config)
    {
        $this->data = $this->filter($config->export());
        $this->dirty = true;

        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface::import()
     */
    public function merge(ConfigurationInterface $config)
    {
        $this->data = array_merge($this->data, $this->filter($config->export()));
        $this->dirty = true;

        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface::export()
     */
    public function export()
    {
        return array_diff_key($this->data, array_flip($this->blacklist));
    }

    /**
     * @param  array $data
     * @return array
     */
    protected function filter(array $data)
    {
        $blacklist = $this->blacklist;

        return array_filter(
            $data,
            function ($key) use ($blacklist) {
                return !in_array($key, $blacklist, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
