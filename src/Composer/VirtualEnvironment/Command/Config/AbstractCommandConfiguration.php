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
use Sjorek\Composer\VirtualEnvironment\Config\LockConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Json\JsonFile;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
abstract class AbstractCommandConfiguration extends AbstractConfiguration implements CommandConfigurationInterface
{
    const REGEXP_EXPANSION = '#\{\$([^\}]+)\}#u';

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

        $this->set('lock', $input->getOption('lock') ? true : false);
        $this->set('force', $input->getOption('force'));
        $this->set('remove', $input->getOption('remove'));

        $composerFile = Factory::getComposerFile();
        $filesystem = new Filesystem();
        $this->set(
            'base-dir',
            $filesystem->normalizePath(realpath(realpath(dirname($composerFile))))
        );
        $binDir = $this->set(
            'bin-dir',
            $this->composer->getConfig()->get('bin-dir', Config::RELATIVE_PATHS)
        );
        $this->set(
            'bin-dir-up',
            implode('/', array_map(function () {
                return '..';
            }, explode('/', $binDir)))
        );
        $vendorDir = $this->set(
            'vendor-dir',
            $this->composer->getConfig()->get('vendor-dir', Config::RELATIVE_PATHS)
        );
        $this->set(
            'vendor-dir-up',
            implode('/', array_map(function () {
                return '..';
            }, explode('/', $vendorDir)))
        );

        $this->set('composer-venv-dir', '.composer-venv');

        return $this->setup();
    }

    /**
     * @return bool
     */
    abstract protected function setup();

    /**
     * {@inheritDoc}
     * @see FileConfigurationInterface::save()
     */
    public function save($force = false)
    {
        $output = $this->output;
        $recipe = $this->recipe;
        if (!($recipe instanceof LockConfiguration)) {
            $this->prepareSave($recipe);
        }
        if ($recipe->save($force)) {
            $output->writeln(
                sprintf(
                    '<comment>Saving configuration "%s" succeeded.</comment>',
                    $recipe->file()
                ),
                OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
            );

            return true;
        }

        $output->writeln(
            sprintf(
                '<error>Saving configuration "%s" failed.</error>',
                $recipe->file()
            )
        );

        return false;
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
    public function lock($load = false)
    {
        $lock = new LockConfiguration($this->composer, $this->recipe->file());
        if ($lock->load() && $load) {
            $this->merge($lock);
        } else {
            $lock = $this->prepareLock($lock)->merge($this);
        }
        $this->recipe = $lock;
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
                $expand = $this->parseExpansion($key, Config::RELATIVE_PATHS, $config, $manifest);
                if (isset($result[$expand])) {
                    $this->output->writeln(
                        sprintf(
                            '<error>Duplicate entry found while expanding configuration: %s vs %s</error>',
                            $key,
                            $expand
                        )
                    );
                    continue;
                }
            } else {
                $expand = $key;
            }
            $result[$expand] = $this->parseExpansion($value, Config::RELATIVE_PATHS, $config, $manifest);
        }

        return $result;
    }

    /**
     * Replaces {$refs} and %ENV% inside a string
     *
     * @param  string     $value
     * @param  int        $flags    Options (see class constants)
     * @param  Config     $config
     * @param  array|null $manifest
     * @return string
     */
    protected function parseExpansion($value, $flags = 0, Config $config = null, array $manifest = null)
    {
        return Platform::expandPath(
            $this->parseConfig(
                Platform::expandPath(
                    $this->parseManifest(
                        Platform::expandPath($value),
                        $manifest
                    )
                ),
                $flags,
                $config
            )
        );
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
        $command = $this->export();

        return preg_replace_callback(
            static::REGEXP_EXPANSION,
            function ($match) use ($command, $config, $flags) {
                $path = str_getcsv($match[1], '.');
                $value = $command;
                foreach ($path as $key) {
                    if (isset($value[$key])) {
                        $value = $value[$key];
                    } else {
                        $value = null;
                        break;
                    }
                }
                if (is_string($value)) {
                    return $value;
                } else {
                    return $config->has($match[1]) ? $config->get($match[1], $flags) : $match[0];
                }
            },
            $value
        );
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
        }

        return preg_replace_callback(
            static::REGEXP_EXPANSION,
            function ($match) use ($manifest) {
                $path = str_getcsv($match[1], '.');
                $value = $manifest;
                foreach ($path as $key) {
                    if (isset($value[$key])) {
                        $value = $value[$key];
                    } else {
                        return $match[0];
                    }
                }

                return is_string($value) ? $value : $match[0];
            },
            $value
        );
    }
}
