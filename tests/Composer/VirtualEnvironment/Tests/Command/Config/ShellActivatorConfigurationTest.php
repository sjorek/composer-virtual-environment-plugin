<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Tests\Command\Config;

use PHPUnit\Framework\TestCase;
use Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorConfiguration;

/**
 * ActivationScriptProcessor test case.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class ShellActivatorConfigurationTest extends TestCase
{
    public function provideCheckValidateData()
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
            'detection returns shell for supported shell' => array(
                array('bash'), array('detect'), '/absolute/path/to/bash',
            ),
            'detection returns empty for unsupported shell' => array(
                array(), array('detect'), '/absolute/path/to/xxsh',
            ),
            'all available return all available' => array(
                explode(',', ShellActivatorConfiguration::AVAILABLE_ACTIVATORS),
                explode(',', ShellActivatorConfiguration::AVAILABLE_ACTIVATORS),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorConfiguration::validate
     * @dataProvider provideCheckValidateData
     *
     * @param array       $expected
     * @param array       $candidates
     * @param string|null $shell
     * @see ShellActivatorConfiguration::validate()
     */
    public function checkValidate(array $expected, array $candidates, $shell = null)
    {
        if ($shell !== null) {
            $_SERVER['SHELL'] = $shell;
        }
        $this->assertEquals($expected, ShellActivatorConfiguration::validate($candidates));
    }

    public function provideCheckTranslateData()
    {
        return array(
            'empty activators return empty activator scripts' => array(
                array(), array(),
            ),
            'one activator returns activator script' => array(
                array('activate.bash'), array('bash'),
            ),
            'two activators, but not bash or zsh, return two activator scripts' => array(
                array('activate.csh', 'activate.fish'), array('csh', 'fish'),
            ),
            'two activators, with one of them being bash, return two activator scripts' => array(
                array('activate.bash', 'activate.csh'), array('bash', 'csh'),
            ),
            'two activators, with one of them being zsh, return two activator scripts' => array(
                array('activate.csh', 'activate.zsh'), array('csh', 'zsh'),
            ),
            'bash- and zsh-activator return three activator scripts' => array(
                array('activate', 'activate.bash', 'activate.zsh'), array('bash', 'zsh'),
            ),
            'all available return all available plus one' => array(
                array_merge(
                    array('activate'),
                    array_map(
                        function ($activator) {
                            return 'activate.' . $activator;
                        },
                        explode(',', ShellActivatorConfiguration::AVAILABLE_ACTIVATORS)
                    )
                ),
                explode(',', ShellActivatorConfiguration::AVAILABLE_ACTIVATORS),
            ),
        );
    }

    /**
     * @test
     * @covers \Sjorek\Composer\VirtualEnvironment\Command\Config\ShellActivatorConfiguration::translate
     * @dataProvider provideCheckTranslateData
     *
     * @param array $expected
     * @param array $candidates
     * @see ShellActivatorConfiguration::translate()
     */
    public function checkTranslate(array $expected, array $candidates)
    {
        $this->assertEquals($expected, ShellActivatorConfiguration::translate($candidates));
    }
}
