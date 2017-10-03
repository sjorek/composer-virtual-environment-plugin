<?php
use Composer\Plugin\PluginInterface;
use Composer\Composer;
use Composer\IO\IOInterface;

/**
 * A plugin providing a command to activate/deactivate the current bin directory
 * in shell, optionally placing a symlink to the current php-binary.
 * 
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellPlugin implements PluginInterface
{

    /**
     * (non-PHPdoc)
     *
     * @see \Composer\Plugin\PluginInterface::activate()
     *
     */
    public function activate(Composer $composer, IOInterface $io)
    {}

    public function getCapabilities()
    {
        return array(
            'Composer\\Plugin\\Capability\\CommandProvider' => 'Sjorek\\Composer\\ShellPlugin\\Capability\\CommandProvider',
        );
    }
}

