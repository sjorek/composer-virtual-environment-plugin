<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Tests\Processor\ShellHook;

use Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\FileProcessor;
use Sjorek\Composer\VirtualEnvironment\Tests\Processor\AbstractProcessorTestCase;

/**
 * FileProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class FileProcessorTest extends AbstractProcessorTestCase
{
    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\FileProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @see FileProcessor::__construct()
     */
    public function check__construct()
    {
        $this->assertInstanceOf(
            FileProcessor::class,
            new FileProcessor(null, null, null, null, null, null)
        );
    }

    public function provideCheckDeployData()
    {
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
                'The shell-hook file vfs://test/target/post-activate.d/00-test.sh already exists.',
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
            ),
            'target already exists, forced removal' => array(
                true,
                'Removed existing shell-hook file vfs://test/target/post-activate.d/00-test.sh.',
                array('source' => array('source.sh' => 'X'), 'target' => array('post-activate.d' => array('00-test.sh' => 'X'))),
                array('source' => array('source.sh' => 'X'), 'target' => array('post-activate.d' => array('00-test.sh' => 'Y'))),
                true,
            ),
            'target already exists, forced removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the existing shell-hook file vfs:\/\/test\/target\/post-activate\.d\/00-test\.sh: Could not delete/',
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
                true,
                0555,
            ),
            'missing template' => array(
                false,
                'The shell-hook file template vfs://test/source/source.sh does not exist.',
                array(),
            ),
            'template is not readable' => array(
                false,
                'Failed to fetch the shell-hook file template vfs://test/source/source.sh.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                true,
                null,
                0222,
            ),
            'no permission to create target directory' => array(
                false,
                'Failed to create the shell-hook file target directory vfs://test/target/post-activate.d: vfs://test/target/post-activate.d does not exist and could not be created.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                true,
                0555,
            ),
            'no permission to write target file' => array(
                false,
                'Failed to write the shell-hook file vfs://test/target/post-activate.d/00-test.sh.',
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array())),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array())),
                true,
                0555,
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed existing shell-hook file vfs://test/target/post-activate.d/00-test.sh.',
                    'Installed shell-hook file vfs://test/target/post-activate.d/00-test.sh.',
                    '',
                ),
                array('source' => array('source.sh' => 'X'), 'target' => array('post-activate.d' => array('00-test.sh' => 'X'))),
                array('source' => array('source.sh' => 'X'), 'target' => array('post-activate.d' => array('00-test.sh' => 'Y'))),
                true,
                0755,
                0644,
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\FileProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::deploy()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\FileProcessor::deployHook()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::deployTemplate()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::fetchTemplate()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::renderTemplate()
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
     * @param string $target
     * @param string $shell
     * @see FileProcessor::deploy()
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
        $target = 'source/source.sh',
        $shell = null
    ) {
        $file = '00-test.sh';
        $dir = sprintf('%s/%s.d', dirname($hook), basename($hook));
        $root = $this->setupVirtualFilesystem(
            $filesystem,
            array($dir . '/' . $file, $target),
            $directoryMode,
            $fileMode
        );
        $target = $root->url() . '/' . $target;
        $hook = $root->url() . '/' . $hook;
        $processor = new FileProcessor(
            basename($hook),
            '00-test',
            $shell,
            $target,
            $root->url(),
            dirname($hook)
        );

        $this->assertDeployment(
            $expectedResult,
            $expectedOutput,
            $expectedFilesystem,
            $root->url() . '/' . $dir . '/' . $file,
            $root,
            $processor,
            $force
        );
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
                'Refused to remove the shell-hook file vfs://test/target/post-activate.d/00-test.sh, as it is a symbolic link.',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
            ),
            'removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the shell-hook file vfs:\/\/test\/target\/post-activate\.d\/00-test\.sh: Could not delete/',
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                0555,
            ),
            'refuse removal of dangling symlink' => array(
                false,
                'Refused to remove the shell-hook file vfs://test/target/post-activate.d/00-test.sh, as it is a dangling symbolic link.',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'dangling symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'dangling symlink'))),
                0555,
            ),
            'skip removing missing file' => array(
                true,
                'Skipped removing the shell-hook file vfs://test/target/post-activate.d/00-test.sh, as it does not exist.',
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array())),
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed shell-hook file vfs://test/target/post-activate.d/00-test.sh.',
                    '',
                ),
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\FileProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::rollback()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\FileProcessor::rollbackHook()
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
     * @see FileProcessor::rollback()
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
        $file = '00-test.sh';
        $dir = sprintf('%s/%s.d', dirname($hook), basename($hook));
        $root = $this->setupVirtualFilesystem(
            $filesystem,
            array($dir . '/' . $file),
            $directoryMode,
            $fileMode
         );
        $hook = $root->url() . '/' . $hook;
        $processor = new FileProcessor(
            basename($hook),
            '00-test',
            null,
            'test',
            $root->url(),
            dirname($hook)
        );

        $this->assertRollback(
            $expectedResult,
            $expectedOutput,
            $expectedFilesystem,
            $root->url() . '/' . $dir . '/' . $file,
            $root,
            $processor
        );
    }
}
