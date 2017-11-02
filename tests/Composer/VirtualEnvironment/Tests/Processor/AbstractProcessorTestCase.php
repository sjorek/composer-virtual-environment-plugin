<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Tests\Processor;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Sjorek\Composer\VirtualEnvironment\Tests\AbstractVfsStreamTestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Sjorek\Composer\VirtualEnvironment\Processor\ProcessorInterface;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Base test case class for testing Processors.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class AbstractProcessorTestCase extends AbstractVfsStreamTestCase
{
    /**
     * @param bool               $expectedResult
     * @param string             $expectedOutput
     * @param array              $expectedFilesystem
     * @param string             $target
     * @param vfsStreamDirectory $root
     * @param ProcessorInterface $processor
     * @param bool               $force
     * @param int|null           $targetPermission
     */
    protected function assertDeployment(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        $target,
        vfsStreamDirectory $root,
        ProcessorInterface $processor,
        $force = false,
        $targetPermission = null
    ) {
        if ($targetPermission === null) {
            $targetPermission = 0777 & ~umask();
        }
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

        \Composer\Util\vfsFilesystem::$vfs = $root;
        \Composer\Util\vfsFilesystem::$cwd = $root;
        $this->setProtectedProperty($processor, 'filesystem', new \Composer\Util\vfsFilesystem());

        $result = $processor->deploy($io, $force);
        $this->assertSame($expectedResult, $result, 'Assert that result is the same.');

        $this->assertOutput($expectedOutput, $io);

        $this->assertVirtualFilesystem($expectedFilesystem);

        if ($result === true) {
            $this->assertTrue(
                $root->hasChild($target),
                'Assert that the target file exists.'
            );
            if ($targetPermission !== null) {
                $this->assertSame(
                    sprintf('%04o', $targetPermission),
                    sprintf('%04o', $root->getChild($target)->getPermissions()),
                    sprintf('Assert that the target file has %04o permissions.', $targetPermission)
                );
            }
        }
    }

    /**
     * @param bool               $expectedResult
     * @param string             $expectedOutput
     * @param array              $expectedFilesystem
     * @param string             $target
     * @param vfsStreamDirectory $root
     * @param ProcessorInterface $processor
     * @param bool               $force
     */
    protected function assertRollback(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        $target,
        vfsStreamDirectory $root,
        ProcessorInterface $processor,
        $force = false
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

        \Composer\Util\vfsFilesystem::$vfs = $root;
        \Composer\Util\vfsFilesystem::$cwd = $root;
        $this->setProtectedProperty($processor, 'filesystem', new \Composer\Util\vfsFilesystem());

        $result = $processor->rollback($io);
        $this->assertSame($expectedResult, $result, 'Assert that result is the same.');

        $this->assertOutput($expectedOutput, $io);

        $this->assertVirtualFilesystem($expectedFilesystem);

        if ($result === true) {
            $this->assertFalse(
                $root->hasChild($target),
                'Assert that the target file has been removed.'
            );
        }
    }

    /**
     * @param mixed          $expected
     * @param BufferedOutput $io
     */
    protected function assertOutput($expected, BufferedOutput $io)
    {
        $output = explode(PHP_EOL, $io->fetch());
        if (is_array($expected)) {
            $output = array_slice(
                $output,
                0,
                count($expected) ?: 10
            );
            $this->assertEquals($expected, $output, 'Assert that output is equal.');
        } else {
            $output = array_shift($output);
            if ($expected && $expected[0] === '/') {
                $this->assertRegExp($expected, $output, 'Assert that output matches expectation.');
            } else {
                $this->assertSame($expected, $output, 'Assert that output is the same.');
            }
        }
    }

    /**
     * @param array $expected
     */
    protected function assertVirtualFilesystem(array $expected)
    {
        $visitor = new vfsStreamStructureVisitor();
        $actual = vfsStream::inspect($visitor)->getStructure();
        $this->assertEquals($expected, $actual['test'], 'Assert that the filesystem structure is equal.');
    }

    /**
     * @param  array                              $filesystem
     * @param  array                              $files
     * @param  int|null                           $directoryPermissions
     * @param  int|null                           $filePermissions
     * @return \org\bovigo\vfs\vfsStreamDirectory
     */
    protected function setupVirtualFilesystem(
        array $filesystem,
        array $files = array(),
        $directoryPermissions = null,
        $filePermissions = null
    ) {
        $defaultPermissions = 0777 & ~umask();
        $root = vfsStream::setup('test', $directoryPermissions ?: $defaultPermissions, $filesystem);
        foreach ($files as $file) {
            if (strpos($file, '/') === false || strpos($file, '://') !== false) {
                continue;
            }
            if ($root->hasChild($file)) {
                $root->getChild($file)->chmod($filePermissions ?: $defaultPermissions);
            }
            if ($root->hasChild(dirname($file))) {
                $root->getChild(dirname($file))->chmod($directoryPermissions ?: $defaultPermissions);
            }
        }

        return $root;
    }
}
