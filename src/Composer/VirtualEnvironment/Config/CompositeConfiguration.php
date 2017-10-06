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

use Composer\Composer;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class CompositeConfiguration implements ConfigurationInterface
{
    /**
     * @var GlobalConfiguration
     */
    public $global;

    /**
     * @var LocalConfiguration
     */
    public $local;

    public $updateLocal;
    public $ignoreLocal;
    public $updateGlobal;
    public $ignoreGlobal;

    /**
     */
    public function __construct(
        Composer $composer,
        $updateLocal = false,
        $ignoreLocal = false,
        $updateGlobal = false,
        $ignoreGlobal = false
    ) {
        $this->local = new LocalConfiguration($composer);
        $this->updateLocal = $updateLocal;
        $this->ignoreLocal = $ignoreLocal;

        $this->global = new GlobalConfiguration($composer);
        $this->updateGlobal = $updateGlobal;
        $this->ignoreGlobal = $ignoreGlobal;
    }

    public function all()
    {
        if ($this->ignoreLocal && $this->ignoreGlobal) {
            return array();
        } elseif ($this->ignoreLocal) {
            return $this->global->all();
        } elseif ($this->ignoreGlobal) {
            return $this->local->all();
        }

        return array_unique(array_merge($this->local->all(), $this->global->all()));
    }

    public function has($key)
    {
        if ($this->ignoreLocal && $this->ignoreGlobal) {
            return false;
        } elseif ($this->ignoreLocal) {
            return $this->global->has($key);
        } elseif ($this->ignoreGlobal) {
            return $this->local->has($key);
        }

        return $this->local->has($key) || $this->global->has($key);
    }

    public function get($key, $default = null)
    {
        if ($this->ignoreLocal && $this->ignoreGlobal) {
            return $default;
        } elseif ($this->ignoreLocal) {
            return $this->global->get($key, $default);
        } elseif ($this->ignoreGlobal) {
            return $this->local->get($key, $default);
        }

        return $this->local->get($key, $this->global->get($key, $default));
    }

    public function set($key, $value)
    {
        if ($this->updateLocal) {
            $this->local->set($key, $value);
        }
        if ($this->updateGlobal) {
            $this->global->set($key, $value);
        }

        return $value;
    }

    public function remove($key)
    {
        if ($this->updateLocal) {
            $this->local->remove($key);
        }
        if ($this->updateGlobal) {
            $this->global->remove($key);
        }
    }

    public function load()
    {
        $local = true;
        if (!$this->ignoreLocal) {
            $local = $this->local->load();
        }
        $global = true;
        if (!$this->ignoreGlobal) {
            $global = $this->global->load();
        }

        return $local && $global;
    }

    public function persist($force = false)
    {
        $local = true;
        if ($this->updateLocal) {
            $local = $this->local->persist($force);
        }
        $global = true;
        if ($this->updateGlobal) {
            $global = $this->global->persist($force);
        }

        return $local && $global;
    }
}
