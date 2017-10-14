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

use PHPUnit\Framework\TestCase;
use Sjorek\Composer\VirtualEnvironment\Processor\ActivationScriptProcessor;

/**
 * ActivationScriptProcessor test case.
 */
class ActivationScriptProcessorTest extends TestCase
{
//     /**
//      *
//      * @var ActivationScriptProcessor
//      */
//     private $activationScriptProcessor;

//     /**
//      * Prepares the environment before running a test.
//      */
//     protected function setUp()
//     {
//         parent::setUp();
//         // TODO Auto-generated ActivationScriptProcessorTest::setUp()
//         $this->activationScriptProcessor = new ActivationScriptProcessor(/* parameters */);
//     }

//     /**
//      * Cleans up the environment after running a test.
//      */
//     protected function tearDown()
//     {
//         // TODO Auto-generated ActivationScriptProcessorTest::tearDown()
//         $this->activationScriptProcessor = null;

//         parent::tearDown();
//     }

    public function provideCheckImportData()
    {
        return array(
            'empty candidates return empty activators' => array(array(), array()),
            'nonsense candidates return empty activators' => array(array(), array('nonsense')),
            'upper-case candidate return lower-case activator' => array(array('bash'), array('BASH')),
            'candidate repetitions return unique activator' => array(array('bash'), array('bash', 'BASH')),
        );
    }

    /**
     * @test
     * @dataProvider provideCheckImportData
     *
     * @param array $expected
     * @param array $candidates
     * @see ActivationScriptProcessor::import()
     */
    public function checkImport(array $expected, array $candidates)
    {
        $this->assertEquals($expected, ActivationScriptProcessor::import($candidates));
    }

    public function provideCheckExportData()
    {
        return array(
        );
    }

    /**
     * @test
     * @dataProvider provideCheckExportData
     *
     * @param array $expected
     * @param array $candidates
     * @see ActivationScriptProcessor::export()
     */
    public function checkExport(array $expected, array $candidates)
    {
        $this->assertEquals($expected, ActivationScriptProcessor::export($candidates));
    }

//     /**
//      * @test
//      * @see ActivationScriptProcessor::__construct()
//      */
//     public function test__construct()
//     {
//         // TODO Auto-generated ActivationScriptProcessorTest->test__construct()
//         $this->markTestIncomplete("__construct test not implemented");
//         $this->activationScriptProcessor->__construct(/* parameters */);
//     }

//     /**
//      * @test
//      * @see ActivationScriptProcessor::deploy()
//      */
//     public function testDeploy()
//     {
//         // TODO Auto-generated ActivationScriptProcessorTest->testDeploy()
//         $this->markTestIncomplete("deploy test not implemented");
//         $this->activationScriptProcessor->deploy(/* parameters */);
//     }

//     /**
//      * @test
//      * @see ActivationScriptProcessor::rollback()
//      */
//     public function testRollback()
//     {
//         // TODO Auto-generated ActivationScriptProcessorTest->testRollback()
//         $this->markTestIncomplete("rollback test not implemented");
//         $this->activationScriptProcessor->rollback(/* parameters */);
//     }
}
