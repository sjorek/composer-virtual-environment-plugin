<?php
namespace Sjorek\Composer\VirtualEnvironmentPlugin\Capability;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Sjorek\Composer\VirtualEnvironmentPlugin\Command\VirtualEnvironmentCommand;

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

