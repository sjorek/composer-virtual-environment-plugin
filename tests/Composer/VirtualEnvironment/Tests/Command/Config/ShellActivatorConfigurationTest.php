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
        return array(
            'empty candidates return empty activators' => array(
                array(), array(),
            ),
            'nonsense candidates return empty activators' => array(
                array(), array('nonsense'),
            ),
            'upper-case candidate return lower-case activator' => array(
                array('bash'), array('BASH'),
            ),
            'candidate repetitions return unique activator' => array(
                array('bash'), array('bash', 'BASH'),
            ),
            'detection returns shell for supported shell via _SERVER' => array(
                array('bash'), array('detect'), '/absolute/path/to/bash', null,
            ),
            'detection returns shell for supported shell via _ENV' => array(
                array('bash'), array('detect'), null, '/absolute/path/to/bash',
            ),
            'detection returns empty for unsupported shell' => array(
                array(), array('detect'),
            ),
            'all available return all available' => array(
                ShellActivatorConfiguration::SHELLS,
                ShellActivatorConfiguration::SHELLS,
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorConfiguration::validateActivators
     * @dataProvider provideCheckValidateActivatorsData
     *
     * @param array       $expected
     * @param array       $candidates
     * @param string|null $serverSh
     * @param string|null $envSh
     * @see ShellActivatorConfiguration::validateActivators()
     */
    public function checkValidateActivators(array $expected, array $candidates, $serverSh = 'x', $envSh = 'x')
    {
        $_SERVER['SHELL'] = $serverSh;
        $_ENV['SHELL'] = $envSh;
        $this->assertEquals($expected, ShellActivatorConfiguration::validateActivators($candidates));
    }

    public function provideCheckExpandActivatorsData()
    {
        return array(
            'empty activators return empty activator scripts' => array(
                array(), array(), null,
            ),
            'one activator returns activator script for _SERVER' => array(
                array(
                    'bash' => array(
                        'filename' => 'activate.bash',
                        'shell' => '/custom/path/to/bash',
                    ),
                ),
                array('bash'),
                '/custom/path/to/bash',
                null,
            ),
            'one activator returns activator script for _ENV' => array(
                array(
                    'bash' => array(
                        'filename' => 'activate.bash',
                        'shell' => '/custom/path/to/bash',
                    ),
                ),
                array('bash'),
                null,
                '/custom/path/to/bash',
            ),
            'two activators, but not bash or zsh, return two activator scripts' => array(
                array(
                    'csh' => array(
                        'filename' => 'activate.csh',
                        'shell' => '/usr/bin/env csh',
                    ),
                    'fish' => array(
                        'filename' => 'activate.fish',
                        'shell' => '/usr/bin/env fish',
                    ),
                ),
                array('csh', 'fish'),
            ),
            'two activators, with one of them being bash, return two activator scripts' => array(
                array(
                    'bash' => array(
                        'filename' => 'activate.bash',
                        'shell' => '/usr/bin/env bash',
                    ),
                    'csh' => array(
                        'filename' => 'activate.csh',
                        'shell' => '/usr/bin/env csh',
                    ),
                ),
                array('bash', 'csh'),
            ),
            'two activators, with one of them being zsh, return two activator scripts' => array(
                array(
                    'csh' => array(
                        'filename' => 'activate.csh',
                        'shell' => '/usr/bin/env csh',
                    ),
                    'zsh' => array(
                        'filename' => 'activate.zsh',
                        'shell' => '/usr/bin/env zsh',
                    ),
                ),
                array('csh', 'zsh'),
            ),
            'bash- and zsh-activator return three activator scripts' => array(
                array(
                    'bash' => array(
                        'filename' => 'activate.bash',
                        'shell' => '/usr/bin/env bash',
                    ),
                    'sh' => array(
                        'filename' => 'activate.sh',
                        'shell' => '/bin/sh',
                    ),
                    'zsh' => array(
                        'filename' => 'activate.zsh',
                        'shell' => '/usr/bin/env zsh',
                    ),
                ),
                array('bash', 'zsh'),
            ),
            'all available return all available plus one' => array(
                array(
                    'bash' => array(
                        'filename' => 'activate.bash',
                        'shell' => '/usr/bin/env bash',
                    ),
                    'csh' => array(
                        'filename' => 'activate.csh',
                        'shell' => '/usr/bin/env csh',
                    ),
                    'fish' => array(
                        'filename' => 'activate.fish',
                        'shell' => '/usr/bin/env fish',
                    ),
                    'sh' => array(
                        'filename' => 'activate.sh',
                        'shell' => '/bin/sh',
                    ),
                    'zsh' => array(
                        'filename' => 'activate.zsh',
                        'shell' => '/usr/bin/env zsh',
                    ),
                ),
                ShellActivatorConfiguration::SHELLS,
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
    public function checkExpandActivators(array $expected, array $candidates, $serverSh = 'x', $envSh = 'x')
    {
        $_SERVER['SHELL'] = $serverSh;
        $_ENV['SHELL'] = $envSh;
        $this->assertEquals($expected, ShellActivatorConfiguration::expandActivators($candidates));
    }
}
