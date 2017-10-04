<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephnan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Util;

use Composer\Factory;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class JsonConfiguration
{
    protected $filename;
    protected $data;
    protected $dirty;

    public function __construct()
    {
        $recipe = Factory::getComposerFile();
        $filename = dirname($recipe) . '/' . basename($recipe, '.json') . '.venv';
        $this->filename = $filename;
        $this->data = array();
        $this->dirty = false;
        $this->load();
    }

    public function all()
    {
        return array_keys($this->data);
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    public function set($key, $value)
    {
        $this->dirty = $this->get($key) !== $value;
        $this->data[$key] = $value;
    }

    public function remove($key)
    {
        if ($this->has($key)) {
            unset($this->data[$key]);
            $this->dirty = true;
        }
    }

    public function load()
    {
        if (!file_exists($this->filename)) {
            return false;
        }

        $json = file_get_contents($this->filename, false);
        if ($json === false) {
            return false;
        }
        $data = json_decode($json, true);
        if (null === $data && JSON_ERROR_NONE !== json_last_error()) {
            return false;
        }
        if (!is_array($data)) {
            return false;
        }
        $this->data = $data;
        $this->dirty = false;

        return true;
    }

    public function persist($force = false)
    {
        if ($this->dirty || $force) {
            $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES /*| JSON_FORCE_OBJECT */);
            if ($json === false) {
                return false;
            }
            $result = file_put_contents($this->filename, $json);
            if ($result === false) {
                return false;
            }
            $this->dirty = false;
            return true;
        }
        return false;
    }
}
