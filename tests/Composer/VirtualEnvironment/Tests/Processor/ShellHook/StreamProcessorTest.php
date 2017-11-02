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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\StreamProcessor;
use Sjorek\Composer\VirtualEnvironment\Tests\Processor\AbstractVfsStreamTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * StreamProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class StreamProcessorTest extends AbstractVfsStreamTestCase
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
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

        $file = '00-test.sh';
        $dir = sprintf('%s/%s.d', dirname($hook), basename($hook));

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        foreach (array($target, $dir . '/' . $file) as $file) {
            if ($fileMode !== null && $root->hasChild($file)) {
                $root->getChild($file)->chmod($fileMode);
            }
            if ($directoryMode !== null && $root->hasChild(dirname($file))) {
                $root->getChild(dirname($file))->chmod($directoryMode);
            }
        }
        $hook = $root->url() . '/' . $hook;
        if (strpos($target, '://') === false) {
            $target = $root->url() . '/' . $target;
        }
        $processor = new StreamProcessor(
            basename($hook),
            '00-test',
            $shell,
            $target,
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
     * @see StreamProcessor::rollback()
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
            $processor = new StreamProcessor(
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
