<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Tests\Processor\GitHook;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Sjorek\Composer\VirtualEnvironment\Tests\Processor\AbstractVfsStreamTestCase;
use Sjorek\Composer\VirtualEnvironment\Processor\GitHook\SymbolicLinkProcessor;
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
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\SymbolicLinkProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::__construct
     * @see SymbolicLinkProcessor::__construct()
     */
    public function check__construct()
    {
        $this->assertInstanceOf(
            SymbolicLinkProcessor::class,
            new SymbolicLinkProcessor(null, null, null, null)
        );
    }

    public function provideCheckDeployData()
    {
        return array(
            'refuse to deploy for invalid hook' => array(
                false,
                'Invalid git-hook invalid-hook given.',
                array(),
                array(),
                false,
                null,
                null,
                'target/invalid-hook',
            ),
            'refuse to deploy, when target and source are the same' => array(
                false,
                'Skipped creation of git-hook symbolic link, as source vfs://test/target/pre-commit and target vfs://test/target/pre-commit are the same.',
                array(),
                array(),
                false,
                null,
                null,
                'target/pre-commit',
                'target/pre-commit',
            ),
            'forced removal of existing symlink' => array(
                false,
                'Removed existing file for git-hook symbolic link vfs://test/target/pre-commit.',
                array('target' => array()),
                array('target' => array('pre-commit' => 'symlink')),
                true,
            ),
            'forced removal of existing symlink fails due to lack of permission' => array(
                false,
                '/^Could not remove existing git-hook symbolic link vfs:\/\/test\/target\/pre-commit: Could not delete/',
                array('target' => array('pre-commit' => 'symlink')),
                array('target' => array('pre-commit' => 'symlink')),
                true,
                0555,
            ),
            'skip removal of existing symlink' => array(
                false,
                'Skipped creation of git-hook symbolic link, as the source vfs://test/target/pre-commit already exists.',
                array('target' => array('pre-commit' => 'symlink')),
                array('target' => array('pre-commit' => 'symlink')),
                false,
            ),
            'skip symlink-creation for a dead target' => array(
                false,
                'Skipped creation of git-hook symbolic link, as the target vfs://test/source/source.sh does not exist.',
                array(),
                array(),
            ),
            'create directory for symlink fails due to lack of permission' => array(
                false,
                'Failed to create the git-hook symbolic link directory vfs://test/target: vfs://test/target does not exist and could not be created.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                false,
                0555,
            ),
            'symlink fails due to lack of permission' => array(
                false,
                'Creation of git-hook symbolic link failed for source vfs://test/target/pre-commit and target vfs://test/source/source.sh.',
                array('source' => array('source.sh' => ''), 'target' => array()),
                array('source' => array('source.sh' => ''), 'target' => array()),
                false,
                0555,
            ),
            'everything works as expected for relative symlink' => array(
                true,
                array(
                    'Removed existing file for git-hook symbolic link vfs://test/target/pre-commit.',
                    'Installed git-hook symbolic link vfs://test/target/pre-commit to target vfs://test/source/source.sh.',
                    '',
                ),
                array('source' => array('source.sh' => ''), 'target' => array('pre-commit' => 'symlink ../source/source.sh')),
                array('source' => array('source.sh' => ''), 'target' => array('pre-commit' => '')),
                true,
            ),
            'everything works as expected for relative symlink in same directory' => array(
                true,
                array(
                    'Removed existing file for git-hook symbolic link vfs://test/target/pre-commit.',
                    'Installed git-hook symbolic link vfs://test/target/pre-commit to target source.sh.',
                    '',
                ),
                array('target' => array('pre-commit' => 'symlink source.sh', 'source.sh' => '')),
                array('target' => array('pre-commit' => '', 'source.sh' => '')),
                true,
                null,
                null,
                'target/pre-commit',
                'source.sh',
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\SymbolicLinkProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::deploy()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\SymbolicLinkProcessor::deployHook()
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
        $hook = 'target/pre-commit',
        $source = 'source/source.sh'
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG);

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        foreach (array($hook, $source) as $file) {
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
        $processor = new SymbolicLinkProcessor(basename($hook), $source, $root->url(), dirname($hook));

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
            'refuse to remove an invalid hook' => array(
                false,
                'Invalid git-hook invalid-hook given.',
                array(),
                array(),
                null,
                null,
                'target/invalid-hook'
            ),
            'skip removing non-existent symlink' => array(
                true,
                'Skipped removing git-hook symbolic link, as vfs://test/target/pre-commit does not exist.',
                array(),
            ),
            'fail removing symlink due to lack of permission' => array(
                false,
                '/^Could not remove git-hook symbolic link vfs:\/\/test\/target\/pre-commit: Could not delete/',
                array('target' => array('pre-commit' => 'symlink')),
                array('target' => array('pre-commit' => 'symlink')),
                0555,
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed git-hook symbolic link vfs://test/target/pre-commit.',
                    '',
                ),
                array('target' => array()),
                array('target' => array('pre-commit' => 'symlink')),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\SymbolicLinkProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::rollback()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\SymbolicLinkProcessor::rollbackHook()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkTrait::rollbackSymbolicLink()
     * @dataProvider provideCheckRoolbackData
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
        $hook = 'target/pre-commit'
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG);

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        $target = 'source/source.sh';
        foreach (array($target, $hook) as $file) {
            if ($fileMode !== null && $root->hasChild($file)) {
                $root->getChild($file)->chmod($fileMode);
            }
            if ($directoryMode !== null && $root->hasChild(dirname($file))) {
                $root->getChild(dirname($file))->chmod($directoryMode);
            }
        }
        $target = $root->url() . '/' . $target;
        $hook = $root->url() . '/' . $hook;
        $processor = new SymbolicLinkProcessor(basename($hook), $target, $root->url(), dirname($hook));

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
