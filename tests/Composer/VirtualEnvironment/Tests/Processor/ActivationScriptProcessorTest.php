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
use Sjorek\Composer\VirtualEnvironment\Processor\ActivationScriptProcessor;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * ActivationScriptProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ActivationScriptProcessorTest extends TestCase
{
    public function provideCheckImportData()
    {
        return array(
            'empty candidates return empty activators' => array(
                array(), array(),
            ),
            'nonsense candidates return empty activators' => array(
                array(), array('nonsense'),
            ),
            'upper-case candidate return lower-case activator' => array(
                array('bash'), array('BASH'),
            ),
            'candidate repetitions return unique activator' => array(
                array('bash'), array('bash', 'BASH'),
            ),
            'detection returns shell for supported shell' => array(
                array('bash'), array('detect'), '/absolute/path/to/bash',
            ),
            'detection returns empty for unsupported shell' => array(
                array(), array('detect'), '/absolute/path/to/xxsh',
            ),
            'all available return all available' => array(
                explode(',', ActivationScriptProcessor::AVAILABLE_ACTIVATORS),
                explode(',', ActivationScriptProcessor::AVAILABLE_ACTIVATORS),
            ),
        );
    }

    /**
     * @test
     * @dataProvider provideCheckImportData
     *
     * @param array       $expected
     * @param array       $candidates
     * @param string|null $shell
     * @see ActivationScriptProcessor::import()
     */
    public function checkImport(array $expected, array $candidates, $shell = null)
    {
        if ($shell !== null) {
            $_SERVER['SHELL'] = $shell;
        }
        $this->assertEquals($expected, ActivationScriptProcessor::import($candidates));
    }

    public function provideCheckExportData()
    {
        return array(
            'empty activators return empty activator scripts' => array(
                array(), array(),
            ),
            'one activator returns activator script' => array(
                array('activate.bash'), array('bash'),
            ),
            'two activators, but not bash or zsh, return two activator scripts' => array(
                array('activate.csh', 'activate.fish'), array('csh', 'fish'),
            ),
            'two activators, with one of them being bash, return two activator scripts' => array(
                array('activate.bash', 'activate.csh'), array('bash', 'csh'),
            ),
            'two activators, with one of them being zsh, return two activator scripts' => array(
                array('activate.csh', 'activate.zsh'), array('csh', 'zsh'),
            ),
            'bash- and zsh-activator return three activator scripts' => array(
                array('activate', 'activate.bash', 'activate.zsh'), array('bash', 'zsh'),
            ),
            'all available return all available plus one' => array(
                array_merge(
                    array('activate'),
                    array_map(
                        function ($activator) {
                            return 'activate.' . $activator;
                        },
                        explode(',', ActivationScriptProcessor::AVAILABLE_ACTIVATORS)
                    )
                ),
                explode(',', ActivationScriptProcessor::AVAILABLE_ACTIVATORS),
            ),
        );
    }

    /**
     * @test
     * @dataProvider provideCheckExportData
     *
     * @param array $expected
     * @param array $candidates
     * @see ActivationScriptProcessor::export()
     */
    public function checkExport(array $expected, array $candidates)
    {
        $this->assertEquals($expected, ActivationScriptProcessor::export($candidates));
    }

    /**
     * @test
     * @see ActivationScriptProcessor::__construct()
     */
    public function check__construct()
    {
        $this->assertInstanceOf(
            ActivationScriptProcessor::class,
            new ActivationScriptProcessor(null, null, null, array())
        );
    }

    public function provideCheckDeployData()
    {
        return array(
            'target already exists' => array(
                false,
                'Shell activation script vfs://test/target/target.sh already exists.',
                array('target' => array('target.sh' => '')),
                array('target' => array('target.sh' => '')),
            ),
            'target already exists, forced removal' => array(
                false,
                'Removed existing shell activation script vfs://test/target/target.sh.',
                array('target' => array()),
                array('target' => array('target.sh' => '')),
                true,
            ),
            'target already exists, forced removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the existing shell activation script vfs:\/\/test\/target\/target.sh: Could not delete/',
                array('target' => array('target.sh' => '')),
                array('target' => array('target.sh' => '')),
                true,
                0555,
            ),
            'missing template' => array(
                false,
                'The shell activation script template vfs://test/source/source.sh does not exist.',
                array(),
            ),
            'template is not readable' => array(
                false,
                'Failed to read the template file vfs://test/source/source.sh.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                true,
                null,
                0222,
            ),
            'no permission to create target directory' => array(
                false,
                'Failed to create the target directory vfs://test/target: vfs://test/target does not exist and could not be created.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                true,
                0555,
            ),
            'no permission to write target file' => array(
                false,
                'Failed to write the shell activation script vfs://test/target/target.sh.',
                array('source' => array('source.sh' => ''), 'target' => array()),
                array('source' => array('source.sh' => ''), 'target' => array()),
                true,
                0555,
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed existing shell activation script vfs://test/target/target.sh.',
                    'Installed shell activation script vfs://test/target/target.sh.',
                ),
                array('source' => array('source.sh' => 'X'), 'target' => array('target.sh' => 'Y')),
                array('source' => array('source.sh' => 'X'), 'target' => array('target.sh' => 'Z')),
                true,
                0755,
                0644,
                array('X' => 'Y'),
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
     * @param array  $data
     * @see ActivationScriptProcessor::deploy()
     */
    public function checkDeploy(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        array $filesystem = array(),
        $force = false,
        $directoryMode = null,
        $fileMode = null,
        array $data = array()
    ) {
        $io = new BufferedOutput();

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
        $processor = new ActivationScriptProcessor($source, $target, $root->url(), $data);

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

        if ($root->hasChild($target)) {
            $this->assertTrue(
                $root->getChild($target)->getPermissions() === 0777,
                'Assert that the target file is executable.'
            );
        }
    }

    public function provideCheckRoolbackData()
    {
        return array(
            'refuse removal of symlink' => array(
                false,
                'Refused to remove the shell activation script vfs://test/target/target.sh, as it is a symbolic link.',
                array('target' => array('target.sh' => 'symlink')),
                array('target' => array('target.sh' => 'symlink')),
            ),
            'everything works as expected' => array(
                true,
                'Removed shell activation script vfs://test/target/target.sh.',
                array('target' => array()),
                array('target' => array('target.sh' => '')),
            ),
            'removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the shell activation script vfs:\/\/test\/target\/target.sh: Could not delete/',
                array('target' => array('target.sh' => '')),
                array('target' => array('target.sh' => '')),
                false,
                0555,
            ),
            'refuse removal of dangling symlink' => array(
                false,
                'Refused to remove the shell activation script vfs://test/target/target.sh, as it is a dangling symbolic link.',
                array('target' => array('target.sh' => 'dangling symlink')),
                array('target' => array('target.sh' => 'dangling symlink')),
                false,
                0555,
            ),
            'skip removing missing file' => array(
                true,
                'Skipped removing the shell activation script vfs://test/target/target.sh, as it does not exist.',
                array('target' => array()),
                array('target' => array()),
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
     * @see ActivationScriptProcessor::rollback()
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
        $io = new BufferedOutput();

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
        $processor = new ActivationScriptProcessor($source, $target, $root->url(), array());

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
