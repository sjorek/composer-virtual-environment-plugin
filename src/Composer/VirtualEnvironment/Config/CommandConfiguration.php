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
use Composer\Json\JsonFile;
use Composer\Util\Filesystem;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class CommandConfiguration extends AbstractConfiguration
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
        $this->set('resPath', $filesystem->normalizePath(__DIR__ . '/../../../../res'));
        $binPath = $this->set('binPath', $filesystem->normalizePath($this->composer->getConfig()->get('bin-dir')));

        $name = dirname(getcwd());
        if (file_exists($composerFile)) {
            $composerJson = new JsonFile($composerFile, null, $this->io);
            $manifest = $composerJson->read();
            $name = $manifest['name'];
        }
        if ($input->getOption('name')) {
            $name = $input->getOption('name');
        } else {
            $name = $recipe->get('name', $name);
        }
        $this->set('name', $name);

        $candidates = array(); // explode(',', Processor\ActivationScriptProcessor::AVAILABLE_ACTIVATORS);
        if ($input->getOption('shell')) {
            $candidates = $input->getOption('shell');
        } else {
            $candidates = $recipe->get('shell', $candidates);
        }
        $activators = $this->set(
            'activators',
            Processor\ActivationScriptProcessor::import($candidates)
        );
        $recipe->set('shell', $activators);

        $colorPrompt = false;
        if ($input->getOption('color-prompt')) {
            $colorPrompt = true;
        } else {
            $colorPrompt = $recipe->get('color-prompt', $colorPrompt);
        }
        $this->set('color-prompt', $recipe->set('color-prompt', $colorPrompt));

        $symlinks = array(
            'activate' => null,
            'composer' => null,
            'php' => null,
        );

        // If only has been given, we'll symlink to this activator
        if (count($activators) === 1) {
            $symlinks['activate'] = $binPath . DIRECTORY_SEPARATOR . 'activate.' . $activators[0];
        }
        if ($input->getOption('php')) {
            $symlinks['php'] = $recipe->set(
                'php',
                realpath($input->getOption('php')) ?: $input->getOption('php')
            );
        } else {
            $symlinks['php'] = $recipe->get('php');
        }
        if ($input->getOption('composer')) {
            $symlinks['composer'] = $recipe->set(
                'composer',
                realpath($input->getOption('composer')) ?: $input->getOption('composer')
            );
        } else {
            $symlinks['composer'] = $recipe->get('composer');
        }
        $this->set('symlinks', array_filter($symlinks));
    }

    public function persist($force = false)
    {
        $result = true;
        $recipe = $this->get('recipe');
        $output = $this->output;

        if ($recipe->updateLocal) {
            if ($recipe->local->persist($force)) {
                $output->writeln('Update of local configuration "' . $recipe->local->filename . '" succeeded.');
            } else {
                $output->writeln('    <warning>Updated of local configuration "' . $recipe->local->filename . '" failed.</warning>');
                $result = false;
            }
        }
        if ($recipe->updateGlobal) {
            if ($recipe->global->persist($force)) {
                $output->writeln('Update of global configuration "' . $recipe->global->filename . '" succeeded.');
            } else {
                $output->writeln('    <warning>Updated of global configuration "' . $recipe->global->filename . '" failed.</warning>');
                $result = false;
            }
        }

        return $result;
    }

    protected function getRecipeFilename()
    {
        return null;
    }
}
