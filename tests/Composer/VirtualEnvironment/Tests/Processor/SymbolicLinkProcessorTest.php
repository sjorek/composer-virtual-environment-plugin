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
use Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkProcessor;
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
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkProcessor::__construct
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
                'Failed to create the symbolic link directory vfs://test/source: vfs://test/source does not exist and could not be created.',
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
                    'Removed existing file for symbolic link source.sh.',
                    'Installed symbolic link source.sh to target target.sh.',
                    '',
                ),
                array('source.sh' => 'symlink target.sh', 'target.sh' => ''),
                array('source.sh' => '', 'target.sh' => ''),
                true,
                null,
                null,
                'source.sh',
                'target.sh',
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkProcessor::deploy()
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
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

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
        if (strpos($source, '/') !== false) {
            $source = $root->url() . '/' . $source;
        }
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

    public function provideCheckRollbackData()
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
                0555,
            ),
            'everything works as expected for relative path' => array(
                true,
                array(
                    'Removed symbolic link source.sh.',
                    '',
                ),
                array(),
                array('source.sh' => 'symlink'),
                null,
                null,
                'source.sh',
            ),
            'everything works as expected for absolute path' => array(
                true,
                array(
                    'Removed symbolic link vfs://test/source/source.sh.',
                    '',
                ),
                array('source' => array()),
                array('source' => array('source.sh' => 'symlink')),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkProcessor::rollback()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\SymbolicLinkTrait::rollbackSymbolicLink()
     * @dataProvider provideCheckRollbackData
     *
     * @param bool   $expectedResult
     * @param string $expectedOutput
     * @param array  $expectedFilesystem
     * @param array  $structure
     * @param int    $directoryMode
     * @param int    $fileMode
     * @see SymbolicLinkProcessor::rollback()
     */
    public function checkRollback(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        array $filesystem = array(),
        $directoryMode = null,
        $fileMode = null,
        $source = 'source/source.sh'
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

        $root = vfsStream::setup('test', $directoryMode, $filesystem);
        $target = 'target/target.sh';
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
        if (strpos($source, '/') !== false) {
            $source = $root->url() . '/' . $source;
        }
        $target = $root->url() . '/' . $target;
        $processor = new SymbolicLinkProcessor($source, $target, $root->url());

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
