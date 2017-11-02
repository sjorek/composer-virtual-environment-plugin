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

use Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\StreamProcessor;
use Sjorek\Composer\VirtualEnvironment\Tests\Processor\AbstractProcessorTestCase;

/**
 * StreamProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class StreamProcessorTest extends AbstractProcessorTestCase
{
    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\StreamProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @see StreamProcessor::__construct()
     */
    public function check__construct()
    {
        $this->assertInstanceOf(
            StreamProcessor::class,
            new StreamProcessor(null, null, null, null, null, null)
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
                'The shell-hook stream vfs://test/target/post-activate.d/00-test.sh already exists.',
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
            ),
            'target already exists, forced removal' => array(
                true,
                'Removed existing shell-hook stream vfs://test/target/post-activate.d/00-test.sh.',
                array('source' => array('source.sh' => 'X'), 'target' => array('post-activate.d' => array('00-test.sh' => 'X'))),
                array('source' => array('source.sh' => 'X'), 'target' => array('post-activate.d' => array('00-test.sh' => 'Y'))),
                true,
            ),
            'target already exists, forced removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the existing shell-hook stream vfs:\/\/test\/target\/post-activate\.d\/00-test\.sh: Could not delete/',
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
                true,
                0555,
            ),
            'invalid template url' => array(
                false,
                'Invalid url given for shell-hook stream template ://this is not an url.',
                array(),
                array(),
                false,
                null,
                null,
                'post-activate',
                '://this is not an url',
            ),
            'template http stream url not found' => array(
                false,
                'The shell-hook stream template http://' . WEBSERVER_HOST . ':' . WEBSERVER_PORT . '/non-existant was not found.',
                array(),
                array(),
                false,
                null,
                null,
                'post-activate',
                'http://' . WEBSERVER_HOST . ':' . WEBSERVER_PORT . '/non-existant',
            ),
            'missing template' => array(
                false,
                'The shell-hook stream template vfs://test/source/source.sh does not exist.',
                array(),
            ),
            'template is not readable' => array(
                false,
                'Failed to fetch the shell-hook stream template vfs://test/source/source.sh.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                true,
                null,
                0222,
            ),
            'no permission to create target directory' => array(
                false,
                'Failed to create the shell-hook stream target directory vfs://test/target/post-activate.d: vfs://test/target/post-activate.d does not exist and could not be created.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                true,
                0555,
            ),
            'no permission to write target file' => array(
                false,
                'Failed to write the shell-hook stream vfs://test/target/post-activate.d/00-test.sh.',
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array())),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array())),
                true,
                0555,
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed existing shell-hook stream vfs://test/target/post-activate.d/00-test.sh.',
                    'Installed shell-hook stream vfs://test/target/post-activate.d/00-test.sh.',
                    '',
                ),
                array('source' => array('source.sh' => 'X'), 'target' => array('post-activate.d' => array('00-test.sh' => 'X'))),
                array('source' => array('source.sh' => 'X'), 'target' => array('post-activate.d' => array('00-test.sh' => 'Y'))),
                true,
                0755,
                0644,
            ),
            'everything works as expected for http stream' => array(
                true,
                array(
                    'Removed existing shell-hook stream vfs://test/target/post-activate.d/00-test.sh.',
                    'Installed shell-hook stream vfs://test/target/post-activate.d/00-test.sh.',
                    '',
                ),
                array('target' => array('post-activate.d' => array('00-test.sh' => '# This file has been intentionally left blank!'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                true,
                0755,
                0644,
                'target/post-activate',
                'http://' . WEBSERVER_HOST . ':' . WEBSERVER_PORT . '/blank.txt',
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\StreamProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::deploy()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\StreamProcessor::deployHook()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::deployTemplate()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\StreamProcessor::fetchTemplate()
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
     * @see StreamProcessor::deploy()
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
        $file = sprintf('%s/%s.d/00-test.%s', dirname($hook), basename($hook), $shell ? basename($shell) : 'sh');
        $root = $this->setupVirtualFilesystem(
            $filesystem,
            array($file, $target),
            $directoryMode,
            $fileMode
        );
        $targetVfs = strpos($target, '://') === false ? $root->url() . '/' . $target : $target;
        $hookVfs = $root->url() . '/' . $hook;
        $processor = new StreamProcessor(
            basename($hookVfs),
            '00-test',
            $shell,
            $targetVfs,
            $root->url(),
            dirname($hookVfs)
        );

        $this->assertDeployment(
            $expectedResult,
            $expectedOutput,
            $expectedFilesystem,
            $file,
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
                'Refused to remove the shell-hook stream vfs://test/target/post-activate.d/00-test.sh, as it is a symbolic link.',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
            ),
            'removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the shell-hook stream vfs:\/\/test\/target\/post-activate\.d\/00-test\.sh: Could not delete/',
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
                0555,
            ),
            'refuse removal of dangling symlink' => array(
                false,
                'Refused to remove the shell-hook stream vfs://test/target/post-activate.d/00-test.sh, as it is a dangling symbolic link.',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'dangling symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'dangling symlink'))),
                0555,
            ),
            'skip removing missing file' => array(
                true,
                'Skipped removing the shell-hook stream vfs://test/target/post-activate.d/00-test.sh, as it does not exist.',
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array())),
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed shell-hook stream vfs://test/target/post-activate.d/00-test.sh.',
                    '',
                ),
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array('00-test.sh' => ''))),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\StreamProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::rollback()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\StreamProcessor::rollbackHook()
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
     * @param string $shell
     * @see StreamProcessor::rollback()
     */
    public function checkRollback(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        array $filesystem = array(),
        $directoryMode = null,
        $fileMode = null,
        $hook = 'target/post-activate',
        $shell = null
    ) {
        $file = sprintf('%s/%s.d/00-test.%s', dirname($hook), basename($hook), $shell ? basename($shell) : 'sh');
        $root = $this->setupVirtualFilesystem(
            $filesystem,
            array($file),
            $directoryMode,
            $fileMode
        );
        $hookVfs = $root->url() . '/' . $hook;
        $processor = new StreamProcessor(
            basename($hookVfs),
            '00-test',
            null,
            'test',
            $root->url(),
            dirname($hookVfs)
        );

        $this->assertRollback(
            $expectedResult,
            $expectedOutput,
            $expectedFilesystem,
            $file,
            $root,
            $processor
        );
    }
}
