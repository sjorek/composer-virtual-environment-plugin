<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Tests\Processor\GitHook;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor;
use Sjorek\Composer\VirtualEnvironment\Tests\AbstractVfsStreamTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * ShellActivationHookProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivationHookProcessorTest extends AbstractVfsStreamTestCase
{
    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor::__construct
     * @see ShellActivationHookProcessor::__construct()
     */
    public function check__construct()
    {
        $this->assertInstanceOf(
            ShellActivationHookProcessor::class,
            new ShellActivationHookProcessor(null, null, null, null, null, null)
        );
    }

    public function provideCheckDeployData()
    {
        $hookContent = function ($shebang = '/bin/sh') {
            return implode(
                PHP_EOL,
                array(
                    '#!' . $shebang,
                    '# post-activate shell-hook script generated by composer-virtual-environment-plugin',
                    'test',
                    '',
                )
            );
        };

        return array(
            'refuse to deploy for invalid hook' => array(
                false,
                'Invalid shell-hook invalid-hook given.',
                array(),
                array(),
                false,
                null,
                null,
                'target/invalid-hook',
            ),
            'target already exists' => array(
                false,
                'The shell-hook script vfs://test/target/post-activate.d/00-test.sh already exists.',
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
            ),
            'target already exists, forced removal' => array(
                true,
                'Removed existing shell-hook script vfs://test/target/post-activate.d/00-test.sh.',
                array('target' => array('post-activate.d' => array('00-test.sh' => $hookContent()))),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                true,
            ),
            'target already exists, forced removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the existing shell-hook script vfs:\/\/test\/target\/post-activate\.d\/00-test\.sh: Could not delete/',
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                true,
                0555,
            ),
            'fail to fetch empty template' => array(
                false,
                'Failed to fetch the shell-hook script template .',
                array(),
                array(),
                false,
                null,
                null,
                'target/post-activate',
                '',
            ),
            'no permission to create target directory' => array(
                false,
                'Failed to create the shell-hook script target directory vfs://test/target/post-activate.d: vfs://test/target/post-activate.d does not exist and could not be created.',
                array(),
                array(),
                true,
                0555,
            ),
            'no permission to write target file' => array(
                false,
                'Failed to write the shell-hook script vfs://test/target/post-activate.d/00-test.sh.',
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array())),
                true,
                0555,
            ),
            'warn about invalid path to shell' => array(
                true,
                'The shebang executable "/invalid/sh" does not exist for shell-hook script: test',
                array('target' => array('post-activate.d' => array('00-test.sh' => $hookContent('/invalid/sh')))),
                array('target' => array('post-activate.d' => array())),
                false,
                null,
                null,
                'target/post-activate',
                'test',
                '/invalid/sh',
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed existing shell-hook script vfs://test/target/post-activate.d/00-test.bash.',
                    'Installed shell-hook script vfs://test/target/post-activate.d/00-test.bash.',
                    '',
                ),
                array('target' => array('post-activate.d' => array('00-test.bash' => $hookContent('/usr/bin/env bash')))),
                array('target' => array('post-activate.d' => array('00-test.bash' => 'Y'))),
                true,
                0755,
                0644,
                'target/post-activate',
                'test',
                'bash',
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor::deploy()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::deployTemplate()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor::fetchTemplate()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor::renderTemplate()
     * @dataProvider provideCheckDeployData
     *
     * @param bool   $expectedResult
     * @param string $expectedOutput
     * @param array  $expectedFilesystem
     * @param array  $structure
     * @param bool   $force
     * @param int    $directoryMode
     * @param int    $fileMode
     * @param string $hook
     * @param string $script
     * @param string $shell
     * @see ShellActivationHookProcessor::deploy()
     */
    public function checkDeploy(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        array $filesystem = array(),
        $force = false,
        $directoryMode = null,
        $fileMode = null,
        $hook = 'target/post-activate',
        $script = 'test',
        $shell = null
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);
        $file = '00-test.sh';
        $dir = sprintf('%s/%s.d', dirname($hook), basename($hook));

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        if ($fileMode !== null && $root->hasChild($dir . '/' . $file)) {
            $root->getChild($dir . '/' . $file)->chmod($fileMode);
        }
        if ($directoryMode !== null && $root->hasChild($dir)) {
            $root->getChild($dir)->chmod($directoryMode);
        }
        $hook = $root->url() . '/' . $hook;
        $processor = new ShellActivationHookProcessor(
            basename($hook),
            '00-test',
            $shell,
            $script,
            $root->url(),
            dirname($hook)
        );

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

        if ($root->hasChild($hook)) {
            $this->assertTrue(
                $root->getChild($hook)->getPermissions() === 0777,
                'Assert that the target file is executable.'
            );
        }
    }

    public function provideCheckRollbackData()
    {
        return array(
            'refuse to remove an invalid hook' => array(
                false,
                'Invalid shell-hook invalid-hook given.',
                array(),
                array(),
                null,
                null,
                'target/invalid-hook',
            ),
            'refuse removal of symlink' => array(
                false,
                'Refused to remove the shell-hook script vfs://test/target/post-activate.d/00-test.sh, as it is a symbolic link.',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
            ),
            'removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the shell-hook script vfs:\/\/test\/target\/post-activate\.d\/00-test\.sh: Could not delete/',
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                0555,
            ),
            'refuse removal of dangling symlink' => array(
                false,
                'Refused to remove the shell-hook script vfs://test/target/post-activate.d/00-test.sh, as it is a dangling symbolic link.',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'dangling symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'dangling symlink'))),
                0555,
            ),
            'skip removing missing file' => array(
                true,
                'Skipped removing the shell-hook script vfs://test/target/post-activate.d/00-test.sh, as it does not exist.',
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array())),
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed shell-hook script vfs://test/target/post-activate.d/00-test.sh.',
                    '',
                ),
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationHookProcessor::rollback()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::rollbackTemplate()
     * @dataProvider provideCheckRollbackData
     *
     * @param bool   $expectedResult
     * @param string $expectedOutput
     * @param array  $expectedFilesystem
     * @param array  $structure
     * @param int    $directoryMode
     * @param int    $fileMode
     * @param string $hook
     * @see ShellActivationHookProcessor::rollback()
     */
    public function checkRollback(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        array $filesystem = array(),
        $directoryMode = null,
        $fileMode = null,
        $hook = 'target/post-activate'
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);
        $file = '00-test.sh';
        $dir = sprintf('%s/%s.d', dirname($hook), basename($hook));

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        if ($fileMode !== null && $root->hasChild($dir . '/' . $file)) {
            $root->getChild($dir . '/' . $file)->chmod($fileMode);
        }
        if ($directoryMode !== null && $root->hasChild($dir)) {
            $root->getChild($dir)->chmod($directoryMode);
        }
        $hook = $root->url() . '/' . $hook;
        $processor = new ShellActivationHookProcessor(
            basename($hook),
            '00-test',
            null,
            'test',
            $root->url(),
            dirname($hook)
        );

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
}
