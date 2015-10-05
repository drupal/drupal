<?php

namespace Behat\Mink\Tests\Exception;

use Behat\Mink\Exception\ElementException;

/**
 * @group legacy
 */
class ElementExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testMessage()
    {
        $exception = new ElementException($this->getElementMock(), new \Exception('Something went wrong'));

        $expectedMessage = "Exception thrown by element XPath\nSomething went wrong";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals($expectedMessage, (string) $exception);
    }

    public function testElement()
    {
        $element = $this->getElementMock();

        $exception = new ElementException($element, new \Exception('Something went wrong'));

        $this->assertSame($element, $exception->getElement());
    }

    private function getElementMock()
    {
        $mock = $this->getMockBuilder('Behat\Mink\Element\Element')
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())
            ->method('getXPath')
            ->will($this->returnValue('element XPath'));

        return $mock;
    }
}
