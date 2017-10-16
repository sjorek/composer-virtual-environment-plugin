<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command\Config;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Sjorek\Composer\VirtualEnvironment\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sjorek\Composer\VirtualEnvironment\Config\LocalConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\GlobalConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\FileConfiguration;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractConfiguration extends Config\AbstractConfiguration implements ConfigurationInterface
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Config\FileConfigurationInterface
     */
    public $recipe;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Composer        $composer
     * @param IOInterface     $io
     */
    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        Composer $composer,
        IOInterface $io
    ) {
        parent::__construct($composer);
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Command\Config\ConfigurationInterface::load()
     */
    public function load()
    {
        $input = $this->input;

        $load = null;
        if ($input->getOption('config')) {
            $filename = $input->getOption('config');
            if ($filename === '-') {
                $filename = 'php://stdin';
            }
            $load = new FileConfiguration($this->composer, $filename);
        } elseif ($input->getOption('local')) {
            $load = new LocalConfiguration($this->composer);
        } elseif ($input->getOption('global')) {
            $load = new GlobalConfiguration($this->composer);
        } else {
            $load = new LocalConfiguration($this->composer);
            if (!file_exists($load->filename)) {
                $load = new GlobalConfiguration($this->composer);
                if (!file_exists($load->filename)) {
                    $load = new FileConfiguration($this->composer, 'php://memory');
                }
            }
        }

        $save = null;
        if ($input->getOption('save')) {
            if ($input->getOption('config')) {
                $filename = $input->getOption('config');
                if ($filename === '-') {
                    $filename = 'php://output';
                }
                $save = new FileConfiguration($this->composer, $filename);
            } elseif ($input->getOption('local')) {
                $save = new LocalConfiguration($this->composer);
            } elseif ($input->getOption('global')) {
                $save = new GlobalConfiguration($this->composer);
            } else {
                $save = new LocalConfiguration($this->composer);
            }
        }

        if ($load === null && $save === null) {
            $this->set('load', false);
            $this->set('save', false);
        } elseif ($load !== null && $save !== null) {
            $this->set('load', $load->load());
            $this->set('save', true);
            $this->recipe = $save->import($load);
        } elseif ($save !== null) {
            $this->set('load', $save->load());
            $this->set('save', true);
            $this->recipe = $save;
        } elseif ($load !== null) {
            $this->set('load', $load->load());
            $this->set('save', false);
            $this->recipe = $load;
        }

        $composerFile = Factory::getComposerFile();
        $filesystem = new Filesystem();

        $this->set('basePath', $filesystem->normalizePath(realpath(realpath(dirname($composerFile)))));
        $this->set('force', $input->getOption('force'));
        $this->set('remove', $input->getOption('remove'));

        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Command\Config\ConfigurationInterface::save()
     */
    public function save($force = false)
    {
        if ($this->get('save')) {
            $output = $this->output;
            $recipe = $this->recipe;
            if ($recipe->save($force)) {
                $output->writeln('<comment>Saving configuration "' . $recipe->filename . '" succeeded.</comment>');

                return true;
            } else {
                $output->writeln('<warning>Saving configuration "' . $recipe->filename . '" failed.</warning>');

                return false;
            }
        }

        return true;
    }
}
