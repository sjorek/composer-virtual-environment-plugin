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
use Sjorek\Composer\VirtualEnvironment\Tests\AbstractVfsStreamTestCase;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\SymbolicLinkProcessor;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * SymbolicLinkProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class SymbolicLinkProcessorTest extends AbstractVfsStreamTestCase
{
    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\SymbolicLinkProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @see SymbolicLinkProcessor::__construct()
     */
    public function check__construct()
    {
        $this->assertInstanceOf(
            SymbolicLinkProcessor::class,
            new SymbolicLinkProcessor(null, null, null, null, null, null)
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
            'refuse to deploy, when target and source are the same' => array(
                false,
                'Skipped creation of shell-hook symbolic link, as source vfs://test/target/post-activate.d/00-test.sh and target vfs://test/target/post-activate.d/00-test.sh are the same.',
                array(),
                array(),
                false,
                null,
                null,
                'target/post-activate',
                'target/post-activate.d/00-test.sh',
            ),
            'forced removal of existing symlink' => array(
                false,
                'Removed existing file for shell-hook symbolic link vfs://test/target/post-activate.d/00-test.sh.',
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                true,
            ),
            'forced removal of existing symlink fails due to lack of permission' => array(
                false,
                '/^Could not remove existing shell-hook symbolic link vfs:\/\/test\/target\/post-activate\.d\/00-test\.sh: Could not delete/',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                true,
                0555,
            ),
            'skip removal of existing symlink' => array(
                false,
                'Skipped creation of shell-hook symbolic link, as the source vfs://test/target/post-activate.d/00-test.sh already exists.',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                false,
            ),
            'skip symlink-creation for a dead target' => array(
                false,
                'Skipped creation of shell-hook symbolic link, as the target vfs://test/source/source.sh does not exist.',
                array(),
                array(),
            ),
            'create directory for symlink fails due to lack of permission' => array(
                false,
                'Failed to create the shell-hook symbolic link directory vfs://test/target/post-activate.d: vfs://test/target/post-activate.d does not exist and could not be created.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                false,
                0555,
            ),
            'symlink fails due to lack of permission' => array(
                false,
                'Creation of shell-hook symbolic link failed for source vfs://test/target/post-activate.d/00-test.sh and target vfs://test/source/source.sh.',
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array())),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array())),
                false,
                0555,
            ),
            'everything works as expected for relative symlink' => array(
                true,
                array(
                    'Removed existing file for shell-hook symbolic link vfs://test/target/post-activate.d/00-test.sh.',
                    'Installed shell-hook symbolic link vfs://test/target/post-activate.d/00-test.sh to target vfs://test/source/source.sh.',
                    '',
                ),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => 'symlink ../../source/source.sh'))),
                array('source' => array('source.sh' => ''), 'target' => array('post-activate.d' => array('00-test.sh' => ''))),
                true,
            ),
            'everything works as expected for relative symlink in same directory' => array(
                true,
                array(
                    'Removed existing file for shell-hook symbolic link vfs://test/target/post-activate.d/00-test.sh.',
                    'Installed shell-hook symbolic link vfs://test/target/post-activate.d/00-test.sh to target source.sh.',
                    '',
                ),
                array('target' => array('post-activate.d' => array('source.sh' => '', '00-test.sh' => 'symlink source.sh'))),
                array('target' => array('post-activate.d' => array('source.sh' => '', '00-test.sh' => ''))),
                true,
                null,
                null,
                'target/post-activate',
                'source.sh',
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\SymbolicLinkProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::deploy()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\SymbolicLinkProcessor::deployHook()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkTrait::deploySymbolicLink()
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
     * @param string $source
     * @param string $shell
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
        $hook = 'target/post-activate',
        $source = 'source/source.sh',
        $shell = null
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

        $file = '00-test.sh';
        $dir = sprintf('%s/%s.d', dirname($hook), basename($hook));

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        foreach (array($dir . '/' . $file, $source) as $file) {
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
        $hook = $root->url() . '/' . $hook;
        if (strpos($source, '/') !== false) {
            $source = $root->url() . '/' . $source;
        }
        $processor = new SymbolicLinkProcessor(
            basename($hook),
            '00-test',
            $shell,
            $source,
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
            'skip removing non-existent symlink' => array(
                true,
                'Skipped removing shell-hook symbolic link, as vfs://test/target/post-activate.d/00-test.sh does not exist.',
                array(),
            ),
            'fail removing symlink due to lack of permission' => array(
                false,
                '/^Could not remove shell-hook symbolic link vfs:\/\/test\/target\/post-activate\.d\/00-test\.sh: Could not delete/',
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
                0555,
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed shell-hook symbolic link vfs://test/target/post-activate.d/00-test.sh.',
                    '',
                ),
                array('target' => array('post-activate.d' => array())),
                array('target' => array('post-activate.d' => array('00-test.sh' => 'symlink'))),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\SymbolicLinkProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\AbstractProcessor::rollback()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellHook\SymbolicLinkProcessor::rollbackHook()
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
     * @see SymbolicLinkProcessor::rollback()
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
        $processor = new SymbolicLinkProcessor(
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
