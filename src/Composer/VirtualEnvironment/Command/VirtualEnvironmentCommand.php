<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephnan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Command;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Sjorek\Composer\VirtualEnvironment\Processor;
use Sjorek\Composer\VirtualEnvironment\Util\RecipeConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class VirtualEnvironmentCommand extends BaseCommand
{
    protected function configure()
    {
        $io = $this->getIO();
        $composerFile = Factory::getComposerFile();
        $composerJson = new JsonFile($composerFile, null, $io);
        $manifest = $composerJson->read();

        $recipe = new RecipeConfiguration();

        $name = $recipe->get('name', $manifest['name']);
        $activators = $recipe->get(
            'shell',
            explode(',', Processor\ActivationScriptProcessor::AVAILABLE_ACTIVATORS)
        );

        $oldPath = getenv('PATH');
        if ($oldPath) {
            putenv('PATH=' . implode( PATH_SEPARATOR, array_slice( explode( PATH_SEPARATOR, $oldPath ), 1 )));
        }
        $php = $recipe->get('php', exec('which php') ?: null);
        if ($oldPath) {
            putenv('PATH=' . $oldPath);
        }
        $composer = $recipe->get('composer', realpath($_SERVER['argv'][0]) ?: null);

        $useRecipe = file_exists($recipe->filename);

        $this
            ->setName('virtual-environment')
            ->setDescription('Setup a virtual environment.')
            ->setDefinition(array(
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the virtual environment. Takes precedence over recipe and global composer configuration.', $name),
                new InputOption('shell', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Set the list of shell activators to deploy. Takes precedence over recipe and global composer configuration.', $activators),
                new InputOption('php', null, InputOption::VALUE_REQUIRED, 'Add symlink to php. Takes precedence over recipe and global composer configuration.', $php),
                new InputOption('composer', null, InputOption::VALUE_REQUIRED, 'Add symlink to composer. Takes precedence over recipe and global composer configuration.', $composer),
                $useRecipe
                    ? new InputOption('use-recipe', null, InputOption::VALUE_OPTIONAL, 'Use and update the virtual environment configuration recipe in "' . $recipe->filename . '" recipe. Takes precedence over global composer configuration.', $useRecipe)
                    : new InputOption('use-recipe', null, InputOption::VALUE_NONE, 'Use and update the virtual environment configuration recipe in "' . $recipe->filename . '" recipe. Takes precedence over global composer configuration.'),
                new InputOption('use-composer', null, InputOption::VALUE_NONE, 'Use and update the global composer configuration.'),
                new InputOption('force', "f", InputOption::VALUE_NONE, 'Force overwriting existing environment scripts'),
            ))
            ->setHelp(
                <<<EOT
The <info>virtual-environment</info> command creates files to activate
and deactivate the current bin directory in shell,
optionally placing a symlinks to php- and composer-binaries
in the bin directory.

Usage:

<info>php composer.phar virtual-environment</info>

After this you can source the activation-script
corresponding to your shell:

bash/zsh:

    <info>source vendor/bin/activate</info>

csh:

    <info>source vendor/bin/activate.csh</info>

fish:

    <info>. vendor/bin/activate.fish</info>

bash (alternative):

    <info>source vendor/bin/activate.bash</info>

zsh (alternative):

    <info>source vendor/bin/activate.zsh</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $config = $composer->getConfig();

        $io = $this->getIO();
        $composerFile = Factory::getComposerFile();
        $composerJson = new JsonFile($composerFile, null, $io);
        $manifest = $composerJson->read();

        $recipe = new RecipeConfiguration();

        $filesystem = new Filesystem();
        $basePath = $filesystem->normalizePath(realpath(realpath(dirname($composerFile))));
        $binPath = $filesystem->normalizePath($config->get('bin-dir'));
        $resPath = $filesystem->normalizePath(__DIR__ . '/../../../../res');

        $name = $manifest['name'];
        if ($input->getOption('name')) {
            $name = $input->getOption('name');
        } elseif ($input->getOption('use-recipe')) {
            $name = $recipe->get('name', $name);
        }
        if ($input->getOption('use-recipe')) {
            $recipe->set('name', $name);
        }
        $data = array(
            '@NAME@' => $name,
            '@BASE_DIR@' => $basePath,
            '@BIN_DIR@' => $binPath,
        );

        $candidates = explode(',', Processor\ActivationScriptProcessor::AVAILABLE_ACTIVATORS);
        if ($input->getOption('shell')) {
            $candidates = $input->getOption('shell');
        } elseif ($input->getOption('use-recipe')) {
            $candidates = $recipe->get('shell', $candidates);
        }
        $activators = Processor\ActivationScriptProcessor::importConfiguration($candidates);

        foreach ($activators as $filename) {
            $source = $resPath . DIRECTORY_SEPARATOR .$filename;
            $target = $binPath . DIRECTORY_SEPARATOR .$filename;
            $processor = new Processor\ActivationScriptProcessor($source, $target, $data);
            if ($processor->deploy($output, $input->getOption('force'))) {
                continue;
            }
        }
        if ($input->getOption('use-recipe')) {
            $activators = Processor\ActivationScriptProcessor::exportConfiguration($activators);
            $recipe->set('shell', $activators);
        }

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
            $symlinks['php'] = realpath($input->getOption('php')) ?: $input->getOption('php');
        } elseif ($input->getOption('use-recipe')) {
            $symlinks['php'] = $recipe->get('php', $symlinks['php']);
        }
        if ($input->getOption('composer')) {
            $symlinks['composer'] = realpath($input->getOption('composer')) ?: $input->getOption('composer');
        } elseif ($input->getOption('use-recipe')) {
            $symlinks['composer'] = $recipe->get('composer', $symlinks['composer']);
        }
        $symlinks = array_filter($symlinks);

        if (empty($symlinks)) {
            $output->writeln('Skipping creation of symbolic links, none available.');
        } elseif (Platform::isWindows()) {
            $output->writeln('    <warning>Symbolic links are not supported on windows</warning>');
        } else {
            foreach ($symlinks as $name => $source) {
                $target = $binPath . DIRECTORY_SEPARATOR . $name;
                $processor = new Processor\SymbolicLinkProcessor($source, $target);
                if ($processor->deploy($output, $input->getOption('force'))) {
                    if ($input->getOption('use-recipe') && $name !== 'activate') {
                        $recipe->set($name, $source);
                    }
                }
            }
        }
        if ($input->getOption('use-recipe') && $recipe->persist()) {
            $output->writeln('Updated virtual environment configuration recipe: ' . $recipe->filename);
        }
    }
}
