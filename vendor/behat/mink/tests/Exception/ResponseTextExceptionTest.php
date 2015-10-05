<?php

namespace Behat\Mink\Tests\Exception;

use Behat\Mink\Exception\ResponseTextException;

class ResponseTextExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionToString()
    {
        $driver = $this->getMock('Behat\Mink\Driver\DriverInterface');

        $driver->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));
        $driver->expects($this->any())
            ->method('getCurrentUrl')
            ->will($this->returnValue('http://localhost/test'));
        $driver->expects($this->any())
            ->method('getText')
            ->with('//html')
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

        $exception = new ResponseTextException('Text error', $driver);

        $this->assertEquals($expected, $exception->__toString());
    }
}
