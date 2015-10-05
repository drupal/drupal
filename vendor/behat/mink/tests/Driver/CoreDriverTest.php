<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Element\NodeElement;

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

    public function testCreateNodeElements()
    {
        $driver = $this->getMockBuilder('Behat\Mink\Driver\CoreDriver')
            ->setMethods(array('findElementXpaths'))
            ->getMockForAbstractClass();

        $session = $this->getMockBuilder('Behat\Mink\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $driver->setSession($session);

        $driver->expects($this->once())
            ->method('findElementXpaths')
            ->with('xpath')
            ->willReturn(array('xpath1', 'xpath2'));

        /** @var NodeElement[] $elements */
        $elements = $driver->find('xpath');

        $this->assertInternalType('array', $elements);
        $this->assertCount(2, $elements);
        $this->assertContainsOnlyInstancesOf('Behat\Mink\Element\NodeElement', $elements);

        $this->assertSame('xpath1', $elements[0]->getXpath());
        $this->assertSame('xpath2', $elements[1]->getXpath());
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

        if ('setSession' === $method->getName()) {
            return; // setSession is actually implemented, so we don't expect an exception here.
        }

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
