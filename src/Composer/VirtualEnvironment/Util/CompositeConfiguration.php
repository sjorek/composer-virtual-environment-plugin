<?php
namespace Composer\VirtualEnvironment\Util;

use Composer\Composer;
use Composer\Config;
use Sjorek\Composer\VirtualEnvironment\Util\RecipeConfiguration;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class CompositeConfiguration
{

    /**
     * @var Config
     */
    protected $composer;

    /**
     * @var RecipeConfiguration
     */
    protected $recipe;

    /**
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer->getConfig();
        $this->recipe = new RecipeConfiguration();
    }

    public function all()
    {
        $all = $this->recipe->all();
//         $this->composer->get($key);
    }

    public function has($key)
    {
    }

    public function get($key, $default = null)
    {
    }

    public function set($key, $value)
    {
    }

    public function remove($key)
    {
    }

    public function load()
    {
        return true;
    }

    public function persist($force = false)
    {
        return false;
    }
}

