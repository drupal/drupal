<?php

namespace Behat\Mink\Tests\Exception;

use Behat\Mink\Exception\ExpectationException;

class ExpectationExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyMessageAndPreviousException()
    {
        $exception = new ExpectationException('', $this->getSessionMock(), new \Exception('Something failed'));

        $this->assertEquals('Something failed', $exception->getMessage());
    }

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

        $html = "<html><head><title>Hello</title></head>\n<body>\n<h1>Hello world</h1>\n<p>Test</p>\n</body></html>";
        $page->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($html));

        $expected = <<<'TXT'
Expectation failure

+--[ HTTP/1.1 200 | http://localhost/test | %s ]
|
|  <body>
|  <h1>Hello world</h1>
|  <p>Test</p>
|  </body>
|
TXT;

        $expected = sprintf($expected.'  ', get_class($driver));

        $exception = new ExpectationException('Expectation failure', $session);

        $this->assertEquals($expected, $exception->__toString());
    }

    public function testBigContent()
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

        $body = str_repeat('a', 1001 - strlen('<body></body>'));

        $html = sprintf("<html><head><title>Hello</title></head>\n<body>%s</body></html>", $body);
        $page->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($html));

        $expected = <<<'TXT'
Expectation failure

+--[ HTTP/1.1 200 | http://localhost/test | %s ]
|
|  <body>%s</b...
|
TXT;

        $expected = sprintf($expected.'  ', get_class($driver), $body);

        $exception = new ExpectationException('Expectation failure', $session);

        $this->assertEquals($expected, $exception->__toString());
    }

    public function testExceptionWhileRenderingString()
    {
        $session = $this->getSessionMock();
        $session->expects($this->any())
            ->method('getPage')
            ->will($this->throwException(new \Exception('Broken page')));

        $exception = new ExpectationException('Expectation failure', $session);

        $this->assertEquals('Expectation failure', $exception->__toString());
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
