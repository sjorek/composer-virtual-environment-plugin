<?php

/*
 * This file is part of the Composer Virtual Environment Plugin project.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base test case class for testing with vfsStreams.
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class AbstractTestCase extends TestCase
{
    /**
     * @param  mixed  $object
     * @param  string $propertyName
     * @param  mixed  $value
     * @return mixed
     */
    protected function setProtectedProperty($objectOrClass, $propertyName, $value)
    {
        $class = new \ReflectionClass(is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue(is_object($objectOrClass) ? $objectOrClass : null, $value);
    }
}
