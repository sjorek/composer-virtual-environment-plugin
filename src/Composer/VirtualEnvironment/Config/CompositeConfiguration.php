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

    public $saveLocal;
    public $skipLocal;
    public $saveGlobal;
    public $skipGlobal;

    /**
     */
    public function __construct(
        Composer $composer,
        $saveLocal = false,
        $skipLocal = false,
        $saveGlobal = false,
        $skipGlobal = false
    ) {
        $this->local = new LocalConfiguration($composer);
        $this->saveLocal = $saveLocal;
        $this->skipLocal = $skipLocal;

        $this->global = new GlobalConfiguration($composer);
        $this->saveGlobal = $saveGlobal;
        $this->skipGlobal = $skipGlobal;
    }

    public function all()
    {
        if ($this->skipLocal && $this->skipGlobal) {
            return array();
        } elseif ($this->skipLocal) {
            return $this->global->all();
        } elseif ($this->skipGlobal) {
            return $this->local->all();
        }

        return array_unique(array_merge($this->local->all(), $this->global->all()));
    }

    public function has($key)
    {
        if ($this->skipLocal && $this->skipGlobal) {
            return false;
        } elseif ($this->skipLocal) {
            return $this->global->has($key);
        } elseif ($this->skipGlobal) {
            return $this->local->has($key);
        }

        return $this->local->has($key) || $this->global->has($key);
    }

    public function get($key, $default = null)
    {
        if ($this->skipLocal && $this->skipGlobal) {
            return $default;
        } elseif ($this->skipLocal) {
            return $this->global->get($key, $default);
        } elseif ($this->skipGlobal) {
            return $this->local->get($key, $default);
        }

        return $this->local->get($key, $this->global->get($key, $default));
    }

    public function set($key, $value)
    {
        if ($this->saveLocal) {
            $this->local->set($key, $value);
        }
        if ($this->saveGlobal) {
            $this->global->set($key, $value);
        }

        return $value;
    }

    public function remove($key)
    {
        if ($this->saveLocal) {
            $this->local->remove($key);
        }
        if ($this->saveGlobal) {
            $this->global->remove($key);
        }
    }

    public function load()
    {
        $local = true;
        if (!$this->skipLocal) {
            $local = $this->local->load();
        }
        $global = true;
        if (!$this->skipGlobal) {
            $global = $this->global->load();
        }

        return $local && $global;
    }

    public function persist($force = false)
    {
        $local = true;
        if ($this->saveLocal) {
            $local = $this->local->persist($force);
        }
        $global = true;
        if ($this->saveGlobal) {
            $global = $this->global->persist($force);
        }

        return $local && $global;
    }
}
