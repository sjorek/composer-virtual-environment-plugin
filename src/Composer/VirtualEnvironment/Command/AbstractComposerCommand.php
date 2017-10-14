<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command;

use Composer\Command\BaseCommand;
use Composer\IO\IOInterface;
use Composer\Composer;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractComposerCommand extends BaseCommand
{
    /**
     * Constructor.
     *
     * @param string|null $name     The name of the command; passing null means it must be set in configure()
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @throws \Symfony\Component\Console\Exception\LogicException When the command name is empty
     */
    public function __construct($name = null, Composer $composer = null, IOInterface $io = null)
    {
        if ($composer !== null) {
            $this->setComposer($composer);
        }
        if ($io !== null) {
            $this->setIO($io);
        }
        parent::__construct($name);
    }
}
