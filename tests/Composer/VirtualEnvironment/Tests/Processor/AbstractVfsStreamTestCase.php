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

if (!function_exists(__NAMESPACE__ . '\\realpath')) {
    /**
     * Override realpath() in current namespace for testing with vfsStream
     *
     * @param  string $path the file path
     * @return string
     */
    function realpath($path)
    {
        return $path;
    }
}

namespace Sjorek\Composer\VirtualEnvironment\Config\Command;

if (!function_exists(__NAMESPACE__ . '\\realpath')) {
    /**
     * Override realpath() in current namespace for testing with vfsStream
     *
     * @param  string $path the file path
     * @return string
     */
    function realpath($path)
    {
        return $path;
    }
}

namespace Sjorek\Composer\VirtualEnvironment\Processor;

if (!function_exists(__NAMESPACE__ . '\\file_exists')) {
    /**
     * Override file_exists() in current namespace for testing with vfsStream
     *
     * @param  string $target
     * @return string
     */
    function file_exists($target)
    {
        return \file_exists($target) && @file_get_contents($target, false) !== 'dangling symlink';
    }
}

if (!function_exists(__NAMESPACE__ . '\\is_link')) {
    /**
     * Override is_link() in current namespace for testing with vfsStream
     *
     * @param  string $target
     * @return string
     */
    function is_link($target)
    {
        return (
            \file_exists($target) &&
            ($content = (string) @file_get_contents($target, false)) &&
            (
                substr($content, 0, 7) === 'symlink' ||
                $content === 'dangling symlink'
            )
        );
    }
}

if (!function_exists(__NAMESPACE__ . '\\symlink')) {
    /**
     * Override symlink() in current namespace for testing with vfsStream
     *
     * @param  string $target
     * @param  string $link
     * @return string
     */
    function symlink($target, $link)
    {
        return ! (
            file_exists($link) ||
            is_link($link) ||
            @file_put_contents($link, 'symlink ' . $target) === false
        );
    }
}

namespace Composer\Util;

if (!class_exists(__NAMESPACE__ . '\\vfsFilesystem', false)) {
    /**
     * Override Filesystem in current namespace for testing with vfsStream
     */
    class vfsFilesystem extends Filesystem
    {
        /**
         * @var \org\bovigo\vfs\vfsStreamDirectory
         */
        public static $vfs;

        /**
         * @var \org\bovigo\vfs\vfsStreamDirectory
         */
        public static $cwd;

        public function __construct(ProcessExecutor $executor = null)
        {
            parent::__construct($executor);
        }

        /**
         * Checks if the given path is absolute
         *
         * @param  string $path
         * @return bool
         */
        public function isAbsolutePath($path)
        {
            return substr($path, 0, 6) === 'vfs://' || parent::isAbsolutePath($path);
        }
    }
}

if (!function_exists(__NAMESPACE__ . '\\chdir')) {
    /**
     * Override chdir() in current namespace for testing with vfsStream
     *
     * @param  string $directory
     * @return string
     */
    function chdir($directory)
    {
        $directory = vfsFilesystem::$cwd->url() . '/' . trim($directory, '/') . '/';
        if (
            vfsFilesystem::$cwd->hasChild($directory) &&
            vfsFilesystem::$cwd->getChild($directory) instanceof \org\bovigo\vfs\vfsStreamDirectory
        ) {
            vfsFilesystem::$cwd = $directory;

            return true;
        }

        return false;
    }
}

if (!function_exists(__NAMESPACE__ . '\\symlink')) {
    /**
     * Override symlink() in current namespace for testing with vfsStream
     *
     * @param  string $target
     * @param  string $link
     * @return string
     */
    function symlink($target, $link)
    {
        return \Sjorek\Composer\VirtualEnvironment\Processor\symlink($target, $link);
    }
}

namespace Sjorek\Composer\VirtualEnvironment\Tests\Processor;

use PHPUnit\Framework\TestCase;

/**
 * Base test case class for testing with vfsStreams.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class AbstractVfsStreamTestCase extends TestCase
{
    /**
     * @param  mixed  $object
     * @param  string $propertyName
     * @param  mixed  $value
     * @return mixed
     */
    protected function setProtectedProperty($objectOrClass, $propertyName, $value)
    {
        $class = new \ReflectionClass(is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue(is_object($objectOrClass) ? $objectOrClass : null, $value);
    }
}
