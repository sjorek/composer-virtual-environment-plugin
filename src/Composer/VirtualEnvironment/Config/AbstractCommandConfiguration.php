<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Config;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractCommandConfiguration extends AbstractConfiguration
{
    protected $io;
    protected $input;
    protected $output;

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
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        parent::__construct($composer);
    }

    public function load()
    {
        $input = $this->input;
        $composerFile = Factory::getComposerFile();
        $filesystem = new Filesystem();

        $recipe = $this->set(
            'recipe',
            new CompositeConfiguration(
                $this->composer,
                $input->getOption('update-local'),
                $input->getOption('ignore-local'),
                $input->getOption('update-global'),
                $input->getOption('ignore-global')
            )
        );

        $this->set('basePath', $filesystem->normalizePath(realpath(realpath(dirname($composerFile)))));

        $this->setUp($recipe);
    }

    public function persist($force = false)
    {
        $result = true;
        $output = $this->output;
        $recipe = $this->get('recipe');

        $this->tearDown($recipe);

        if ($recipe->updateLocal) {
            if ($recipe->local->persist($force)) {
                $output->writeln('<comment>Update of local configuration "' . $recipe->local->filename . '" succeeded.</comment>');
            } else {
                $output->writeln('<warning>Updated of local configuration "' . $recipe->local->filename . '" failed.</warning>');
                $result = false;
            }
        }
        if ($recipe->updateGlobal) {
            if ($recipe->global->persist($force)) {
                $output->writeln('<comment>Update of global configuration "' . $recipe->global->filename . '" succeeded.</comment>');
            } else {
                $output->writeln('<warning>Updated of global configuration "' . $recipe->global->filename . '" failed.</warning>');
                $result = false;
            }
        }

        return $result;
    }

    protected function getRecipeFilename()
    {
        return null;
    }

    abstract protected function setUp(ConfigurationInterface $recipe);

    abstract protected function tearDown(ConfigurationInterface $recipe);
}
