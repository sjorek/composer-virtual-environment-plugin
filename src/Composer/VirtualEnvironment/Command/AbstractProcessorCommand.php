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

use Sjorek\Composer\VirtualEnvironment\Config\ConfigurationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\IO\IOInterface;
use Composer\Composer;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractProcessorCommand extends AbstractComposerCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getCommandConfiguration(
            $input,
            $output,
            $this->getComposer(),
            $this->getIO()
        );
        if ($config->get('remove')) {
            $this->rollback($config, $output);
        } else {
            $this->deploy($config, $output);
        }
    }

    /**
     * @param  InputInterface         $input
     * @param  OutputInterface        $output
     * @param  Composer               $composer
     * @param  IOInterface            $io
     * @return ConfigurationInterface
     */
    abstract protected function getCommandConfiguration(
        InputInterface $input,
        OutputInterface $output,
        Composer $composer,
        IOInterface $io
    );

    abstract protected function deploy(
        ConfigurationInterface $config,
        OutputInterface $output
    );

    abstract protected function rollback(
        ConfigurationInterface $config,
        OutputInterface $output
    );
}
