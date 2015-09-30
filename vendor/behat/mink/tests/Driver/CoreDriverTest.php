<?php

namespace Behat\Mink\Tests\Driver;

class CoreDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testNoExtraMethods()
    {
        $interfaceRef = new \ReflectionClass('Behat\Mink\Driver\DriverInterface');
        $coreDriverRef = new \ReflectionClass('Behat\Mink\Driver\CoreDriver');

        foreach ($coreDriverRef->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $this->assertTrue(
                $interfaceRef->hasMethod($method->getName()),
                sprintf('CoreDriver should not implement methods which are not part of the DriverInterface but %s found', $method->getName())
            );
        }
    }

    /**
     * @dataProvider getDriverInterfaceMethods
     */
    public function testInterfaceMethods(\ReflectionMethod $method)
    {
        $refl = new \ReflectionClass('Behat\Mink\Driver\CoreDriver');

        $this->assertFalse(
            $refl->getMethod($method->getName())->isAbstract(),
            sprintf('CoreDriver should implement a dummy %s method', $method->getName())
        );

        $driver = $this->getMockForAbstractClass('Behat\Mink\Driver\CoreDriver');

        $this->setExpectedException('Behat\Mink\Exception\UnsupportedDriverActionException');
        call_user_func_array(array($driver, $method->getName()), $this->getArguments($method));
    }

    public function getDriverInterfaceMethods()
    {
        $ref = new \ReflectionClass('Behat\Mink\Driver\DriverInterface');

        return array_map(function ($method) {
            return array($method);
        }, $ref->getMethods());
    }

    private function getArguments(\ReflectionMethod $method)
    {
        $arguments = array();

        foreach ($method->getParameters() as $parameter) {
            $arguments[] = $this->getArgument($parameter);
        }

        return $arguments;
    }

    private function getArgument(\ReflectionParameter $argument)
    {
        if ($argument->isOptional()) {
            return $argument->getDefaultValue();
        }

        if ($argument->allowsNull()) {
            return null;
        }

        if ($argument->getClass()) {
            return $this->getMockBuilder($argument->getClass()->getName())
                ->disableOriginalConstructor()
                ->getMock();
        }

        return null;
    }
}
