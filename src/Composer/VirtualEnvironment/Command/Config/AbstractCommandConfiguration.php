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
use Composer\Config;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Sjorek\Composer\VirtualEnvironment\Config\AbstractConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\FileConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\FileConfigurationInterface;
use Sjorek\Composer\VirtualEnvironment\Config\GlobalConfiguration;
use Sjorek\Composer\VirtualEnvironment\Config\LocalConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Json\JsonFile;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractCommandConfiguration extends AbstractConfiguration implements CommandConfigurationInterface
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
     * @var FileConfigurationInterface
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
     * @see CommandConfigurationInterface::load()
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
            if (!file_exists($load->file())) {
                $load = new GlobalConfiguration($this->composer);
                if (!file_exists($load->file())) {
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

        if ($this->prepareLoad($load, $save) === false) {
            return false;
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

        $this->set('base-dir', $filesystem->normalizePath(realpath(realpath(dirname($composerFile)))));
        $this->set('lock', $input->getOption('no-lock') ? false : ($input->getOption('lock') ?: true));
        $this->set('force', $input->getOption('force'));
        $this->set('remove', $input->getOption('remove'));

        return $this->finishLoad($load, $save);
    }

    /**
     * @param  FileConfigurationInterface|null $load
     * @param  FileConfigurationInterface|null $save
     * @return bool
     */
    abstract protected function prepareLoad(
        FileConfigurationInterface $load = null,
        FileConfigurationInterface $save = null
    );

    /**
     * @param  FileConfigurationInterface $recipe
     * @return bool
     */
    abstract protected function finishLoad(FileConfigurationInterface $recipe);

    /**
     * {@inheritDoc}
     * @see FileConfigurationInterface::save()
     */
    public function save($force = false)
    {
        if ($this->get('save')) {
            $output = $this->output;
            $recipe = $this->recipe;
            if ($this->prepareSave($recipe)->save($force)) {
                $output->writeln(
                    '<comment>Saving configuration "' . $recipe->filename . '" succeeded.</comment>',
                    OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                );
            } else {
                $output->writeln('<warning>Saving configuration "' . $recipe->filename . '" failed.</warning>');

                return false;
            }
        }

        return true;
    }

    /**
     * @param  FileConfigurationInterface $recipe
     * @return FileConfigurationInterface
     */
    abstract protected function prepareSave(FileConfigurationInterface $recipe);

    /**
     * {@inheritDoc}
     * @see FileConfigurationInterface::lock()
     */
    public function lock($force = false)
    {
        if ($this->get('lock')) {
            $output = $this->output;
            $filename = $this->recipe->file();
            $extension = pathinfo($filename, PATHINFO_EXTENSION) ?: 'json';
            $filename = dirname($filename) . DIRECTORY_SEPARATOR . basename($filename, '.' . $extension) . '.lock';
            $recipe = new FileConfiguration($this->composer, $filename);
            if ($this->prepareLock($recipe)->save($force)) {
                $output->writeln(
                    '<comment>Locking configuration "' . $recipe->filePath . '" succeeded.</comment>',
                    OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
                );
            } else {
                $output->writeln('<warning>Locking configuration "' . $recipe->filePath . '" failed.</warning>');

                return false;
            }
        }

        return true;
    }

    /**
     * @param  FileConfigurationInterface $recipe
     * @return FileConfigurationInterface
     */
    abstract protected function prepareLock(FileConfigurationInterface $recipe);

    /**
     * @param  array $input
     * @param  bool  $expandKey
     * @return array
     */
    protected function expandConfig(array $input, $expandKey = true)
    {
        $result = array();
        $config = $this->composer->getConfig();
        $composerFile = Factory::getComposerFile();
        if (file_exists($composerFile)) {
            $composerJson = new JsonFile($composerFile, null, $this->io);
            $manifest = $composerJson->read();
        } else {
            $manifest = array();
        }
        foreach ($input as $key => $value) {
            if ($expandKey) {
                $expand = Platform::expandPath(
                    $this->parseConfig(
                        Platform::expandPath($this->parseManifest(Platform::expandPath($key), $manifest)),
                        Config::RELATIVE_PATHS,
                        $config
                    )
                );
                if (isset($result[$expand])) {
                    $this->output->writeln(
                        sprintf(
                            '<warning>Duplicate entry found while expanding configuration: %s vs %s</warning>',
                            $key,
                            $expand
                        )
                    );
                    continue;
                }
            } else {
                $expand = $key;
            }
            $result[$expand] = Platform::expandPath(
                $this->parseConfig(
                    Platform::expandPath($this->parseManifest(Platform::expandPath($value), $manifest)),
                    Config::RELATIVE_PATHS,
                    $config
                )
            );
        }

        return $result;
    }

    /**
     * Replaces {$refs} inside a config string
     *
     * @param  string|int|null $value  a config string that can contain {$refs-to-other-config}
     * @param  int             $flags  Options (see class constants)
     * @param  Config          $config
     * @return string|int|null
     */
    protected function parseConfig($value, $flags = 0, Config $config = null)
    {
        if (!is_string($value)) {
            return $value;
        }
        if ($config === null) {
            $config = $this->composer->getConfig();
        }

        return preg_replace_callback('#\{\$(.+)\}#', function ($match) use ($config, $flags) {
            return $config->has($match[1]) ? $config->get($match[1], $flags) : $match[0];
        }, $value);
    }

    /**
     * Replaces {$refs} inside a manifest string
     *
     * @param  string|int|null $value    a config string that can contain {$refs-to-other-config-in-manifest}
     * @param  array|null      $manifest
     * @return string|int|null
     */
    protected function parseManifest($value, array $manifest = null)
    {
        if (!is_string($value)) {
            return $value;
        }
        if ($manifest === null) {
            $composerFile = Factory::getComposerFile();
            if (file_exists($composerFile)) {
                $composerJson = new JsonFile($composerFile, null, $this->io);
                $manifest = $composerJson->read();
            } else {
                return $value;
            }
            $this->composer->getPackage()->getConfig();
        }

        return preg_replace_callback('#\{\$(.+)\}#', function ($match) use ($manifest) {
            if (isset($manifest[$match[1]]) && is_string($manifest[$match[1]])) {
                return $manifest[$match[1]];
            } else {
                return $match[0];
            }
        }, $value);
    }
}
