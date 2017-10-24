<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Tests\Processor;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationScriptProcessor;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * ShellActivationScriptProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivationScriptProcessorTest extends AbstractVfsStreamTestCase
{
    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationScriptProcessor::__construct
     * @see ShellActivationScriptProcessor::__construct()
     */
    public function check__construct()
    {
        $this->assertInstanceOf(
            ShellActivationScriptProcessor::class,
            new ShellActivationScriptProcessor(null, null, null, array())
        );
    }

    public function provideCheckDeployData()
    {
        return array(
            'target already exists' => array(
                false,
                'The shell activation script vfs://test/target/target.sh already exists.',
                array('target' => array('target.sh' => ''), 'source' => array('source.sh' => '')),
                array('target' => array('target.sh' => ''), 'source' => array('source.sh' => '')),
            ),
            'target already exists, forced removal' => array(
                true,
                'Removed existing shell activation script vfs://test/target/target.sh.',
                array('target' => array('target.sh' => 'X'), 'source' => array('source.sh' => 'X')),
                array('target' => array('target.sh' => ''), 'source' => array('source.sh' => 'X')),
                true,
            ),
            'target already exists, forced removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the existing shell activation script vfs:\/\/test\/target\/target.sh: Could not delete/',
                array('target' => array('target.sh' => ''), 'source' => array('source.sh' => '')),
                array('target' => array('target.sh' => ''), 'source' => array('source.sh' => '')),
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
                'Failed to fetch the shell activation script template vfs://test/source/source.sh.',
                array('source' => array('source.sh' => '')),
                array('source' => array('source.sh' => '')),
                true,
                null,
                0222,
            ),
            'no permission to create target directory' => array(
                false,
                'Failed to create the shell activation script target directory vfs://test/target: vfs://test/target does not exist and could not be created.',
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
                    '',
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
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationScriptProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationScriptProcessor::deploy()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::deployTemplate()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::fetchTemplate()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationScriptProcessor::renderTemplate()
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
     * @see ShellActivationScriptProcessor::deploy()
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
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

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
        $processor = new ShellActivationScriptProcessor($source, $target, $root->url(), $data);

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
            'removal fails due to lack of permissions' => array(
                false,
                '/^Failed to remove the shell activation script vfs:\/\/test\/target\/target.sh: Could not delete/',
                array('target' => array('target.sh' => '')),
                array('target' => array('target.sh' => '')),
                0555,
            ),
            'refuse removal of dangling symlink' => array(
                false,
                'Refused to remove the shell activation script vfs://test/target/target.sh, as it is a dangling symbolic link.',
                array('target' => array('target.sh' => 'dangling symlink')),
                array('target' => array('target.sh' => 'dangling symlink')),
                0555,
            ),
            'skip removing missing file' => array(
                true,
                'Skipped removing the shell activation script vfs://test/target/target.sh, as it does not exist.',
                array('target' => array()),
                array('target' => array()),
            ),
            'everything works as expected' => array(
                true,
                array(
                    'Removed shell activation script vfs://test/target/target.sh.',
                    '',
                ),
                array('target' => array()),
                array('target' => array('target.sh' => '')),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationScriptProcessor::__construct
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationScriptProcessor::rollback()
     * @covers \Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait::rollbackTemplate()
     * @dataProvider provideCheckRoolbackData
     *
     * @param bool   $expectedResult
     * @param string $expectedOutput
     * @param array  $expectedFilesystem
     * @param array  $structure
     * @param int    $directoryMode
     * @param int    $fileMode
     * @see ShellActivationScriptProcessor::rollback()
     */
    public function checkRollback(
        $expectedResult,
        $expectedOutput,
        array $expectedFilesystem,
        array $filesystem = array(),
        $directoryMode = null,
        $fileMode = null
    ) {
        $io = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);

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
        $processor = new ShellActivationScriptProcessor($source, $target, $root->url(), array());

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
