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

use Composer\Composer;
use Composer\IO\IOInterface;
use Sjorek\Composer\VirtualEnvironment\Command\Config\GitHookConfiguration;
use Sjorek\Composer\VirtualEnvironment\Command\Config\CommandConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Processor\GitHook;
use Sjorek\Composer\VirtualEnvironment\Processor\GitHook\GitHookProcessorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class GitHookCommand extends AbstractProcessorCommand
{
    protected function configure()
    {
        $this
            ->setName('virtual-environment:git-hook')
            ->setAliases(array('venv:git-hook'))
            ->setDescription('Add or remove virtual environment git-hooks.')
            ->setDefinition(
                $this->addDefaultDefinition(
                    array(
                        new InputArgument(
                            'hook',
                            InputOption::VALUE_OPTIONAL,
                            'List of git-hooks to add or remove.'
                        ),
                        new InputOption(
                            'script',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Use the given script as git-hook.'
                        ),
                        new InputOption(
                            'shebang',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Use the given #!shebang for the given script.'
                        ),
                        new InputOption(
                            'file',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Use the content of the given file as git-hook.'
                        ),
                        new InputOption(
                            'link',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Install git-hook by creating a symbolic link to the given file.'
                        ),
                        new InputOption(
                            'url',
                            null,
                            InputOption::VALUE_REQUIRED,
                            'Download the git-hook from the given url.'
                        ),
                    )
                )
            )
            ->setHelp(
                <<<'EOT'
The <info>virtual-environment:git-hook</info> command manages
git-hooks residing in the <info>.git/hooks</info> directory.

Examples:

Simple shell script using default shebang "#!/bin/sh"

    <info>php composer.phar venv:git-hook pre-commit \
        --script='composer run-script xyz'</info>

Shell script with a more complex shebang

    <info>php composer.phar venv:git-hook pre-commit \
        --shebang='/usr/bin/env bash' \
        --script='echo "about to commit"'</info>

Simple PHP script

    # notice the detection of the correct shebang!
    <info>php composer.phar venv:git-hook pre-commit \
        --script='<?php echo "about to commit";'</info>

Utilizing environment variable expansion

    <info>php composer.phar venv:git-hook pre-commit \
        --shebang=%SHELL% \
        --script='echo "I am using a %SHELL%!"'</info>

Utilizing configuration value expansion

    <info>php composer.phar venv:git-hook pre-commit \
        --shebang='{$bin-dir}/php' \
        --script='<?php
                require "{$vendor-dir}/autoload.php";
                Namespace\Classname::staticMethod();'</info>

Import file from relative path

    <info>php composer.phar venv:git-hook pre-commit \
        --file=relative/path/to/pre-commit.hook</info>

Import file from absolute path

    <info>php composer.phar venv:git-hook pre-commit \
        --file=/absolute/path/to/pre-commit.hook</info>

Create symlink to file

    <info>php composer.phar venv:git-hook pre-commit \
        --link=../../path/to/pre-commit.hook</info>

Relative hook file URL

    <info>php composer.phar venv:git-hook pre-commit \
        --url=file://relative/path/to/pre-commit.hook</info>

Absolute hook file URL

    <info>php composer.phar venv:git-hook pre-commit \
        --url=file:///absolute/path/to/pre-commit.hook</info>

Download hook file from an URL

    <info>php composer.phar venv:git-hook pre-commit \
        --url=https://some.host/pre-commit.hook</info>

Using a built-in hook file URL

    <info>php composer.phar venv:git-hook pre-commit \
        --url=vfs://venv/git-hook/pre-commit.hook</info>

EOT
            );
    }

    /**
     * {@inheritDoc}
     * @see AbstractProcessorCommand::getCommandConfiguration()
     */
    protected function getCommandConfiguration(
        InputInterface $input,
        OutputInterface $output,
        Composer $composer,
        IOInterface $io
    ) {
        return new GitHookConfiguration($input, $output, $composer, $io);
    }

    /**
     * {@inheritDoc}
     * @see AbstractProcessorCommand::deploy()
     */
    protected function deploy(CommandConfigurationInterface $config, OutputInterface $output)
    {
        $hooks = $config->get('git-hook-expanded');
        if (empty($hooks)) {
            $output->writeln(
                '<error>Skipping creation of git-hooks, as none is available.</error>'
            );
        } else {
            $baseDir = $config->get('base-dir', '');
            $gitHookDir = $config->get('git-hook-dir', null);
            foreach ($hooks as $hook => $hookConfig) {
                $processor = $this->getGitHookProcessor($hook, $hookConfig, $baseDir, $gitHookDir);
                if ($processor === null) {
                    $output->writeln(
                        sprintf(
                            '<error>Missing or invalid git-hook type for hook %s.</error>',
                            $hook
                        ),
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );
                    continue;
                }
                $processor->deploy($output, $config->get('force'));
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see AbstractProcessorCommand::rollback()
     */
    protected function rollback(CommandConfigurationInterface $config, OutputInterface $output)
    {
        $hooks = $config->get('git-hook-expanded');
        if (empty($hooks)) {
            $output->writeln(
                '<error>Skipping removal of git-hooks, as none is available.</error>'
            );
        } else {
            $baseDir = $config->get('base-dir', '');
            $gitHookDir = $config->get('git-hook-dir', null);
            foreach ($hooks as $hook => $hookConfig) {
                $processor = $this->getGitHookProcessor($hook, $hookConfig, $baseDir, $gitHookDir);
                if ($processor === null) {
                    $output->writeln(
                        sprintf(
                            '<error>Missing or invalid git-hook type for hook %s.</error>',
                            $hook
                        ),
                        OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                    );
                    continue;
                }
                $processor->rollback($output);
            }
        }
    }

    /**
     * @param  string                         $hook
     * @param  array                          $config
     * @param  string                         $baseDir
     * @param  string|null                    $gitHookDir
     * @return GitHookProcessorInterface|null
     */
    protected function getGitHookProcessor($hook, array $config, $baseDir, $gitHookDir)
    {
        if (isset($config['script'])) {
            $shebang = isset($config['shebang']) ? $config['shebang'] : null;

            return new GitHook\ScriptProcessor($hook, $config['script'], $baseDir, $gitHookDir, $shebang);
        } elseif (isset($config['file'])) {
            return new GitHook\FileProcessor($hook, $config['file'], $baseDir, $gitHookDir);
        } elseif (isset($config['link'])) {
            return new GitHook\SymbolicLinkProcessor($hook, $config['link'], $baseDir, $gitHookDir);
        } elseif (isset($config['url'])) {
            return new GitHook\StreamProcessor($hook, $config['url'], $baseDir, $gitHookDir);
        } else {
            return null;
        }
    }
}
