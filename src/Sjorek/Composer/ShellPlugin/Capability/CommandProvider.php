<?php
namespace Sjorek\Composer\ShellPlugin\Capability;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Sjorek\Composer\ShellPlugin\Command\VirtualEnvironmentCommand;

/**
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class CommandProvider implements CommandProviderCapability
{

    /**
     * (non-PHPdoc)
     *
     * @see \Composer\Plugin\Capability\CommandProvider::getCommands()
     *
     */
    public function getCommands()
    {
        return array(new VirtualEnvironmentCommand());
    }
}

