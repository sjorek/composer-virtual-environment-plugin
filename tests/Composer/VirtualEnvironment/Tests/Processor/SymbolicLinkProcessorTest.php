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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use PHPUnit\Framework\TestCase;
use Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkProcessor;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * SymbolicLinkProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkProcessorTest extends TestCase
{
    /**
     * @test
     * @see SymbolicLinkProcessor::__construct()
     */
    public function check__construct()
    {
        $this->assertInstanceOf(
            SymbolicLinkProcessor::class,
            new SymbolicLinkProcessor(null, null, null)
        );
    }

    public function provideCheckDeployData()
    {
        return array(
            'refuse to deploy, when target and source are the same' => array(
                false,
                'Skipped creation of symbolic link, as source vfs://test/target/target.sh and target vfs://test/target/target.sh are the same.',
                array(),
                array(),
                false,
                null,
                null,
                'target/target.sh',
            ),
            'forced removal of existing symlink' => array(
                false,
                'Removed existing file for symbolic link vfs://test/source/source.sh.',
                array('source' => array()),
                array('source' => array('source.sh' => 'symlink')),
                true,
            ),
            'forced removal of existing symlink fails due to lack of permission' => array(
                false,
                '/^Could not remove existing symbolic link vfs:\/\/test\/source\/source.sh: Could not delete/',
                array('source' => array('source.sh' => 'symlink')),
                array('source' => array('source.sh' => 'symlink')),
                true,
                0555,
            ),
            'skip removal of existing symlink' => array(
                false,
                'Skipped creation of symbolic link, as the source vfs://test/source/source.sh already exists.',
                array('source' => array('source.sh' => 'symlink')),
                array('source' => array('source.sh' => 'symlink')),
                false,
            ),
            'skip symlink-creation for a dead target' => array(
                false,
                'Skipped creation of symbolic link, as the target vfs://test/target/target.sh does not exist.',
                array(),
                array(),
            ),
            'create directory for symlink fails due to lack of permission' => array(
                false,
                'Failed to create the symlink directory vfs://test/source: vfs://test/source does not exist and could not be created.',
                array('target' => array('target.sh' => '')),
                array('target' => array('target.sh' => '')),
                false,
                0555,
            ),
            'symlink fails due to lack of permission' => array(
                false,
                'Creation of symbolic link failed for source vfs://test/source/source.sh and target vfs://test/target/target.sh.',
                array('target' => array('target.sh' => ''), 'source' => array()),
                array('target' => array('target.sh' => ''), 'source' => array()),
                false,
                0555,
            ),
            'everything works as expected for relative symlink' => array(
                true,
                array(
                    'Removed existing file for symbolic link vfs://test/source/source.sh.',
                    'Installed symbolic link vfs://test/source/source.sh to target vfs://test/target/target.sh.',
                    '',
                ),
                array('source' => array('source.sh' => 'symlink ../target/target.sh'), 'target' => array('target.sh' => '')),
                array('source' => array('source.sh' => ''), 'target' => array('target.sh' => '')),
                true,
            ),
            'everything works as expected for relative symlink in same directory' => array(
                true,
                array(
                    'Removed existing file for symbolic link vfs://test/source/source.sh.',
                    'Installed symbolic link vfs://test/source/source.sh to target target.sh.',
                    '',
                ),
                array('source' => array('source.sh' => 'symlink target.sh', 'target.sh' => '')),
                array('source' => array('source.sh' => '', 'target.sh' => '')),
                true,
                null,
                null,
                'source/source.sh',
                'target.sh',
            ),
        );
    }

    /**
     * @test
     * @dataProvider provideCheckDeployData
     *
     * @param bool   $expectedResult
     * @param string $expectedOutput
     * @param array  $expectedFilesystem
     * @param array  $structure
     * @param bool   $force
     * @param int    $directoryMode
     * @param int    $fileMode
     * @param string $source
     * @param string $target
     * @see SymbolicLinkProcessor::deploy()
     */
    public function checkDeploy(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        array $filesystem = array(),
        $force = false,
        $directoryMode = null,
        $fileMode = null,
        $source = 'source/source.sh',
        $target = 'target/target.sh'
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG);

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        foreach (array($source, $target) as $file) {
            if (strpos($file, '/') === false) {
                continue;
            }
            if ($fileMode !== null && $root->hasChild($file)) {
                $root->getChild($file)->chmod($fileMode);
            }
            if ($directoryMode !== null && $root->hasChild(dirname($file))) {
                $root->getChild(dirname($file))->chmod($directoryMode);
            }
        }
        $source = $root->url() . '/' . $source;
        if (strpos($target, '/') !== false) {
            $target = $root->url() . '/' . $target;
        }
        $processor = new SymbolicLinkProcessor($source, $target, $root->url());

        \Composer\Util\vfsFilesystem::$vfs = $root;
        \Composer\Util\vfsFilesystem::$cwd = $root;
        $this->setProtectedProperty($processor, 'filesystem', new \Composer\Util\vfsFilesystem());

        $result = $processor->deploy($io, $force);
        $this->assertSame($expectedResult, $result, 'Assert that result is the same.');

        $output = explode(PHP_EOL, $io->fetch());
        if (is_array($expectedOutput)) {
            $output = array_slice(
                $output,
                0,
                count($expectedOutput) ?: 10
            );
            $this->assertEquals($expectedOutput, $output, 'Assert that output is equal.');
        } else {
            $output = array_shift($output);
            if ($expectedOutput && $expectedOutput[0] === '/') {
                $this->assertRegExp($expectedOutput, $output, 'Assert that output matches expectation.');
            } else {
                $this->assertSame($expectedOutput, $output, 'Assert that output is the same.');
            }
        }

        $visitor = new vfsStreamStructureVisitor();
        $filesystem = vfsStream::inspect($visitor)->getStructure();
        $this->assertEquals(
            $expectedFilesystem,
            $filesystem['test'],
            'Assert that the filesystem structure is equal.'
        );
    }

    public function provideCheckRoolbackData()
    {
        return array(
            'skip removing non-existent symlink' => array(
                true,
                'Skipped removing symbolic link, as vfs://test/source/source.sh does not exist.',
                array(),
            ),
            'fail removing symlink due to lack of permission' => array(
                false,
                '/^Could not remove symbolic link vfs:\/\/test\/source\/source.sh: Could not delete/',
                array('source' => array('source.sh' => 'symlink')),
                array('source' => array('source.sh' => 'symlink')),
                false,
                0555,
            ),
            'everything works as expected' => array(
                true,
                'Removed symbolic link vfs://test/source/source.sh.',
                array('source' => array()),
                array('source' => array('source.sh' => 'symlink')),
            ),
        );
    }

    /**
     * @test
     * @dataProvider provideCheckRoolbackData
     *
     * @param bool   $expectedResult
     * @param string $expectedOutput
     * @param array  $expectedFilesystem
     * @param array  $structure
     * @param bool   $force
     * @param int    $directoryMode
     * @param int    $fileMode
     * @see SymbolicLinkProcessor::rollback()
     */
    public function checkRollback(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        array $filesystem = array(),
        $force = false,
        $directoryMode = null,
        $fileMode = null
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG);

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        $source = 'source/source.sh';
        $target = 'target/target.sh';
        foreach (array($source, $target) as $file) {
            if ($fileMode !== null && $root->hasChild($file)) {
                $root->getChild($file)->chmod($fileMode);
            }
            if ($directoryMode !== null && $root->hasChild(dirname($file))) {
                $root->getChild(dirname($file))->chmod($directoryMode);
            }
        }
        $source = $root->url() . '/' . $source;
        $target = $root->url() . '/' . $target;
        $processor = new SymbolicLinkProcessor($source, $target, $root->url());

        \Composer\Util\vfsFilesystem::$vfs = $root;
        \Composer\Util\vfsFilesystem::$cwd = $root;
        $this->setProtectedProperty($processor, 'filesystem', new \Composer\Util\vfsFilesystem());

        $result = $processor->rollback($io, $force);
        $this->assertSame($expectedResult, $result, 'Assert that result is the same.');

        $output = explode(PHP_EOL, $io->fetch());
        if (is_array($expectedOutput)) {
            $output = array_slice(
                $output,
                0,
                count($expectedOutput) ?: 10
            );
            $this->assertEquals($expectedOutput, $output, 'Assert that output is equal.');
        } else {
            $output = array_shift($output);
            if ($expectedOutput && $expectedOutput[0] === '/') {
                $this->assertRegExp($expectedOutput, $output, 'Assert that output matches expectation.');
            } else {
                $this->assertSame($expectedOutput, $output, 'Assert that output is the same.');
            }
        }

        $visitor = new vfsStreamStructureVisitor();
        $filesystem = vfsStream::inspect($visitor)->getStructure();
        $this->assertEquals(
            $expectedFilesystem,
            $filesystem['test'],
            'Assert that the filesystem structure is equal.'
        );
    }

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
