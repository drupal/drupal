<?php

namespace Behat\Mink\Tests\Selector;

use Behat\Mink\Selector\SelectorsHandler;

class SelectorsHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterSelector()
    {
        $selector = $this->getMockBuilder('Behat\Mink\Selector\SelectorInterface')->getMock();
        $handler = new SelectorsHandler();

        $this->assertFalse($handler->isSelectorRegistered('custom'));

        $handler->registerSelector('custom', $selector);

        $this->assertTrue($handler->isSelectorRegistered('custom'));
        $this->assertSame($selector, $handler->getSelector('custom'));
    }

    public function testRegisterSelectorThroughConstructor()
    {
        $selector = $this->getMockBuilder('Behat\Mink\Selector\SelectorInterface')->getMock();
        $handler = new SelectorsHandler(array('custom' => $selector));

        $this->assertTrue($handler->isSelectorRegistered('custom'));
        $this->assertSame($selector, $handler->getSelector('custom'));
    }

    public function testRegisterDefaultSelectors()
    {
        $handler = new SelectorsHandler();

        $this->assertTrue($handler->isSelectorRegistered('css'));
        $this->assertTrue($handler->isSelectorRegistered('named_exact'));
        $this->assertTrue($handler->isSelectorRegistered('named_partial'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testXpathSelectorThrowsExceptionForArrayLocator()
    {
        $handler = new SelectorsHandler();
        $handler->selectorToXpath('xpath', array('some_xpath'));
    }

    public function testXpathSelectorIsReturnedAsIs()
    {
        $handler = new SelectorsHandler();
        $this->assertEquals('some_xpath', $handler->selectorToXpath('xpath', 'some_xpath'));
    }

    public function testSelectorToXpath()
    {
        $selector = $this->getMockBuilder('Behat\Mink\Selector\SelectorInterface')->getMock();
        $handler = new SelectorsHandler();

        $handler->registerSelector('custom_selector', $selector);

        $selector
            ->expects($this->once())
            ->method('translateToXPath')
            ->with($locator = 'some[locator]')
            ->will($this->returnValue($ret = '[]some[]locator'));

        $this->assertEquals($ret, $handler->selectorToXpath('custom_selector', $locator));

        $this->setExpectedException('InvalidArgumentException');
        $handler->selectorToXpath('undefined', 'asd');
    }

    /**
     * @group legacy
     */
    public function testXpathLiteral()
    {
        $handler = new SelectorsHandler();

        $this->assertEquals("'some simple string'", $handler->xpathLiteral('some simple string'));
    }

    /**
     * @group legacy
     */
    public function testBcLayer()
    {
        $selector = $this->getMockBuilder('Behat\Mink\Selector\SelectorInterface')->getMock();
        $handler = new SelectorsHandler();

        $handler->registerSelector('named_partial', $selector);

        $this->assertSame($selector, $handler->getSelector('named'));
    }
}
