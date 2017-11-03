<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Tests\Command\Config;

use Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorConfiguration;
use Sjorek\Composer\VirtualEnvironment\Tests\AbstractTestCase;

/**
 * ShellActivatorConfiguration test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivatorConfigurationTest extends AbstractTestCase
{
    public function provideCheckValidateActivatorsData()
    {
        $available = array_merge(
            explode(',', ShellActivatorConfiguration::SHELLS_POSIX),
            explode(',', ShellActivatorConfiguration::SHELLS_NT)
        );
        $bash = implode(
            DIRECTORY_SEPARATOR,
            array(
                DIRECTORY_SEPARATOR === '/' ? '' : 'c:',
                'absolute',
                'path',
                'to',
                'bash',
            )
        );

        return array(
            'empty candidates return empty array' => array(
                array(), array(),
            ),
            'nonsense candidate returns false' => array(
                false, array('nonsense'),
            ),
            'nonsense candidate among others returns false' => array(
                false, array('bash', 'nonsense'),
            ),
            'upper-case candidate return lower-case activator' => array(
                array('bash'), array('BASH'),
            ),
            'candidate with ".exe" file-extension return activator with extension stripped' => array(
                array('bash'), array('Bash.exe'),
            ),
            'candidate repetitions return unique activator' => array(
                array('bash'), array('bash', 'BASH', 'Bash.exe'),
            ),
            'detection returns shell for supported shell' => array(
                array('bash'), array('detect'), $bash,
            ),
            'detection returns shell with ".exe" file-extension stripped (cygwin)' => array(
                array('bash'), array('detect'), $bash . '.exe',
            ),
            'detection returns false for unsupported shell' => array(
                false, array('detect'),
            ),
            'detection among others returns false for unsupported shell' => array(
                false, array('bash', 'detect'),
            ),
            'detection among others returns false if SHELL environment variable is not available' => array(
                false, array('detect', 'bash'), null,
            ),
            'all available return all available' => array(
                $available, $available,
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorConfiguration::validateActivators
     * @dataProvider provideCheckValidateActivatorsData
     *
     * @param array|bool  $expected
     * @param array       $candidates
     * @param string|null $shell
     * @see ShellActivatorConfiguration::validateActivators()
     */
    public function checkValidateActivators($expected, array $candidates, $shell = 'none')
    {
        $backup = getenv('SHELL');
        $this->assertTrue(
            putenv(sprintf('SHELL%s', $shell === null ? '' : '=' . $shell)),
            'Failed to override SHELL environment variable.'
        );
        $actual = ShellActivatorConfiguration::validateActivators($candidates);
        putenv(sprintf('SHELL%s', $backup === false ? '' : '=' . $shell));
        if ($expected === false) {
            $this->assertFalse($actual);
        } else {
            $this->assertEquals($expected, $actual);
        }
    }

    public function provideCheckExpandActivatorsData()
    {
        $available = array_merge(
            explode(',', ShellActivatorConfiguration::SHELLS_POSIX),
            explode(',', ShellActivatorConfiguration::SHELLS_NT)
        );

        return array(
            'empty activators return empty activator scripts' => array(
                array(), array(), null,
            ),
            'one activator returns activator script' => array(
                array(
                    'bash' => array(
                        'filenames' => array('activate.bash'),
                        'shell' => '/custom/path/to/bash',
                    ),
                ),
                array('bash'),
                '/custom/path/to/bash',
            ),
            'two activators, but not bash or zsh, return two activator scripts' => array(
                array(
                    'csh' => array(
                        'filenames' => array('activate.csh'),
                        'shell' => '/usr/bin/env csh',
                    ),
                    'fish' => array(
                        'filenames' => array('activate.fish'),
                        'shell' => '/usr/bin/env fish',
                    ),
                ),
                array('csh', 'fish'),
            ),
            'two activators, with one of them being bash, return two activator scripts' => array(
                array(
                    'bash' => array(
                        'filenames' => array('activate.bash'),
                        'shell' => '/usr/bin/env bash',
                    ),
                    'csh' => array(
                        'filenames' => array('activate.csh'),
                        'shell' => '/usr/bin/env csh',
                    ),
                ),
                array('bash', 'csh'),
            ),
            'two activators, with one of them being zsh, return two activator scripts' => array(
                array(
                    'csh' => array(
                        'filenames' => array('activate.csh'),
                        'shell' => '/usr/bin/env csh',
                    ),
                    'zsh' => array(
                        'filenames' => array('activate.zsh'),
                        'shell' => '/usr/bin/env zsh',
                    ),
                ),
                array('csh', 'zsh'),
            ),
            'bash- and zsh-activator return three activator scripts' => array(
                array(
                    'bash' => array(
                        'filenames' => array('activate.bash'),
                        'shell' => '/usr/bin/env bash',
                    ),
                    'sh' => array(
                        'filenames' => array('activate.sh'),
                        'shell' => '/bin/sh',
                    ),
                    'zsh' => array(
                        'filenames' => array('activate.zsh'),
                        'shell' => '/usr/bin/env zsh',
                    ),
                ),
                array('bash', 'zsh'),
            ),
            'all available shells return all shells plus one' => array(
                array(
                    'bash' => array(
                        'filenames' => array('activate.bash'),
                        'shell' => '/usr/bin/env bash',
                    ),
                    'cmd' => array(
                        'filenames' => array('activate.bat', 'deactivate.bat'),
                        'shell' => 'cmd.exe',
                    ),
                    'csh' => array(
                        'filenames' => array('activate.csh'),
                        'shell' => '/usr/bin/env csh',
                    ),
                    'fish' => array(
                        'filenames' => array('activate.fish'),
                        'shell' => '/usr/bin/env fish',
                    ),
                    'powershell' => array(
                        'filenames' => array('Activate.ps1'),
                        'shell' => 'powershell.exe',
                    ),
                    'sh' => array(
                        'filenames' => array('activate.sh'),
                        'shell' => '/bin/sh',
                    ),
                    'zsh' => array(
                        'filenames' => array('activate.zsh'),
                        'shell' => '/usr/bin/env zsh',
                    ),
                ),
                $available,
                '/bin/sh',
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorConfiguration::expandActivators
     * @dataProvider provideCheckExpandActivatorsData
     *
     * @param array       $expected
     * @param array       $candidates
     * @param string|null $shell
     * @see ShellActivatorConfiguration::expandActivators()
     */
    public function checkExpandActivators(array $expected, array $candidates, $shell = 'none')
    {
        $backup = getenv('SHELL');
        $this->assertTrue(
            putenv(sprintf('SHELL%s', $shell === null ? '' : '=' . $shell)),
            'Failed to override SHELL environment variable.'
        );
        $actual = ShellActivatorConfiguration::expandActivators($candidates);
        putenv(sprintf('SHELL%s', $backup === false ? '' : '=' . $shell));
        $this->assertEquals($expected, $actual);
    }
}
