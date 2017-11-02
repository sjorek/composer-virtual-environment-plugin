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
     * @param boolean            $expectedResult
     * @param string             $expectedOutput
     * @param array              $expectedFilesystem
     * @param string             $target
     * @param vfsStreamDirectory $root
     * @param ProcessorInterface $processor
     * @param boolean            $force
     * @param integer|null       $targetPermission
     */
    protected function assertDeployment(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        $target,
        vfsStreamDirectory $root,
        ProcessorInterface $processor,
        $force = false,
        $targetPermission = 0777
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

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

        if ($targetPermission !== null && $root->hasChild($target)) {
            $this->assertTrue(
                $root->getChild($target)->getPermissions() === $targetPermission,
                sprintf('Assert that the target file has %04o permissions.', $targetPermission)
            );
        }
    }

    /**
     * @param boolean            $expectedResult
     * @param string             $expectedOutput
     * @param array              $expectedFilesystem
     * @param string             $target
     * @param vfsStreamDirectory $root
     * @param ProcessorInterface $processor
     * @param boolean            $force
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
     * @param array        $filesystem
     * @param array        $files
     * @param integer|null $directoryPermissions
     * @param integer|null $filePermissions
     * @return \org\bovigo\vfs\vfsStreamDirectory
     */
    protected function setupVirtualFilesystem(
        array $filesystem,
        array $files = array(),
        $directoryPermissions = null,
        $filePermissions = null
    ) {
        $root = vfsStream::setup('test', $directoryPermissions, $filesystem);
        foreach ($files as $file) {
            if (strpos($file, '/') === false || strpos($file, '://') !== false) {
                continue;
            }
            if ($filePermissions !== null && $root->hasChild($file)) {
                $root->getChild($file)->chmod($filePermissions);
            }
            if ($directoryPermissions !== null && $root->hasChild(dirname($file))) {
                $root->getChild(dirname($file))->chmod($directoryPermissions);
            }
        }
        return $root;
    }
}
