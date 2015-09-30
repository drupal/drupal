<?php

namespace Behat\Mink\Tests\Exception;

use Behat\Mink\Exception\ResponseTextException;

class ResponseTextExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionToString()
    {
        $driver = $this->getMock('Behat\Mink\Driver\DriverInterface');
        $page = $this->getPageMock();

        $session = $this->getSessionMock();
        $session->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver));
        $session->expects($this->any())
            ->method('getPage')
            ->will($this->returnValue($page));
        $session->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));
        $session->expects($this->any())
            ->method('getCurrentUrl')
            ->will($this->returnValue('http://localhost/test'));

        $page->expects($this->any())
            ->method('getText')
            ->will($this->returnValue("Hello world\nTest\n"));

        $expected = <<<'TXT'
Text error

+--[ HTTP/1.1 200 | http://localhost/test | %s ]
|
|  Hello world
|  Test
|
TXT;

        $expected = sprintf($expected.'  ', get_class($driver));

        $exception = new ResponseTextException('Text error', $session);

        $this->assertEquals($expected, $exception->__toString());
    }

    private function getSessionMock()
    {
        return $this->getMockBuilder('Behat\Mink\Session')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getPageMock()
    {
        return $this->getMockBuilder('Behat\Mink\Element\DocumentElement')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
