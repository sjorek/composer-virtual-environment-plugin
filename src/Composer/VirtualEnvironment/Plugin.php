<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment;

use Composer\Plugin\PluginInterface;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\Capability\CommandProvider;
use Sjorek\Composer\VirtualEnvironment\Command\GitHookCommand;
use Sjorek\Composer\VirtualEnvironment\Command\ShellActivatorCommand;
use Sjorek\Composer\VirtualEnvironment\Command\SymbolicLinkCommand;
use Sjorek\Composer\VirtualEnvironment\Command\ShellActivatorHookCommand;

/**
 * A plugin providing a command to activate/deactivate the current bin directory
 * in shell, optionally placing a symlink to the current php-binary.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class Plugin implements PluginInterface, Capable, CommandProvider
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function __construct(array $ctorArgs = array())
    {
        if (isset($ctorArgs['composer']) && $ctorArgs['composer'] instanceof Composer) {
            $this->composer = $ctorArgs['composer'];
        }
        if (isset($ctorArgs['io']) && $ctorArgs['io'] instanceof IOInterface) {
            $this->io = $ctorArgs['io'];
        }
    }

    /**
     * {@inheritDoc}
     * @see \Composer\Plugin\PluginInterface::activate()
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
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
     * {@inheritDoc}
     * @see \Composer\Plugin\Capability\CommandProvider::getCommands()
     */
    public function getCommands()
    {
        return array(
            new ShellActivatorCommand(null, $this->composer, $this->io),
            new ShellActivatorHookCommand(null, $this->composer, $this->io),
            new SymbolicLinkCommand(null, $this->composer, $this->io),
            new GitHookCommand(null, $this->composer, $this->io),
        );
    }
}
