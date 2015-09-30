<?php

namespace Behat\Mink\Tests\Exception;

use Behat\Mink\Exception\ElementHtmlException;

class ElementHtmlExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionToString()
    {
        $driver = $this->getMock('Behat\Mink\Driver\DriverInterface');
        $element = $this->getElementMock();

        $session = $this->getSessionMock();
        $session->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver));
        $session->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));
        $session->expects($this->any())
            ->method('getCurrentUrl')
            ->will($this->returnValue('http://localhost/test'));

        $element->expects($this->any())
            ->method('getOuterHtml')
            ->will($this->returnValue("<div>\n    <h1>Hello world</h1>\n    <p>Test</p>\n</div>"));

        $expected = <<<'TXT'
Html error

+--[ HTTP/1.1 200 | http://localhost/test | %s ]
|
|  <div>
|      <h1>Hello world</h1>
|      <p>Test</p>
|  </div>
|
TXT;

        $expected = sprintf($expected.'  ', get_class($driver));

        $exception = new ElementHtmlException('Html error', $session, $element);

        $this->assertEquals($expected, $exception->__toString());
    }

    private function getSessionMock()
    {
        return $this->getMockBuilder('Behat\Mink\Session')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getElementMock()
    {
        return $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
