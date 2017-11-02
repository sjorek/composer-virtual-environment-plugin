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

use Sjorek\Composer\VirtualEnvironment\Processor\ShellActivationScriptProcessor;

/**
 * ShellActivationScriptProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivationScriptProcessorTest extends AbstractProcessorTestCase
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
        $source = 'source/source.sh';
        $target = 'target/target.sh';
        $root = $this->setupVirtualFilesystem(
            $filesystem,
            array($source, $target),
            $directoryMode,
            $fileMode
        );
        $sourceVfs = $root->url() . '/' . $source;
        $targetVfs = $root->url() . '/' . $target;
        $processor = new ShellActivationScriptProcessor($sourceVfs, $targetVfs, $root->url(), $data);

        $this->assertDeployment(
            $expectedResult,
            $expectedOutput,
            $expectedFilesystem,
            $target,
            $root,
            $processor,
            $force
        );
    }

    public function provideCheckRollbackData()
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
     * @dataProvider provideCheckRollbackData
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
        $source = 'source/source.sh';
        $target = 'target/target.sh';
        $root = $this->setupVirtualFilesystem(
            $filesystem,
            array($source, $target),
            $directoryMode,
            $fileMode
        );
        $sourceVfs = $root->url() . '/' . $source;
        $targetVfs = $root->url() . '/' . $target;
        $processor = new ShellActivationScriptProcessor($sourceVfs, $targetVfs, $root->url(), array());

        $this->assertRollback(
            $expectedResult,
            $expectedOutput,
            $expectedFilesystem,
            $target,
            $root,
            $processor
        );
    }
}
