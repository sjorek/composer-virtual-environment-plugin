<?php
namespace Sjorek\Composer;

use Composer\Plugin\PluginInterface;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\Capability\CommandProvider;

/**
 * A plugin providing a command to activate/deactivate the current bin directory
 * in shell, optionally placing a symlink to the current php-binary.
 * 
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class VirtualEnvironmentPlugin implements PluginInterface, Capable, CommandProvider
{

    /**
     *
     * {@inheritDoc}
     * @see \Composer\Plugin\PluginInterface::activate()
     */
    public function activate(Composer $composer, IOInterface $io)
    {}

    /**
     *
     * {@inheritDoc}
     * @see \Composer\Plugin\Capable::getCapabilities()
     */
    public function getCapabilities()
    {
        return array(
            CommandProvider::class => static::class,
        );
    }

    /**
     *
     * {@inheritDoc}
     * @see \Composer\Plugin\Capability\CommandProvider::getCommands()
     */
    public function getCommands()
    {
        return array(new VirtualEnvironmentCommand());
    }
}
