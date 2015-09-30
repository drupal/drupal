<?php

namespace Behat\Mink\Tests\Element;

use Behat\Mink\Element\DocumentElement;

class DocumentElementTest extends ElementTest
{
    /**
     * Page.
     *
     * @var DocumentElement
     */
    private $document;

    protected function setUp()
    {
        parent::setUp();
        $this->document = new DocumentElement($this->session);
    }

    public function testGetSession()
    {
        $this->assertEquals($this->session, $this->document->getSession());
    }

    public function testFindAll()
    {
        $xpath = 'h3[a]';
        $css = 'h3 > a';

        $this->driver
            ->expects($this->exactly(2))
            ->method('find')
            ->will($this->returnValueMap(array(
                array('//html/'.$xpath, array(2, 3, 4)),
                array('//html/'.$css, array(1, 2)),
            )));

        $this->selectors
            ->expects($this->exactly(2))
            ->method('selectorToXpath')
            ->will($this->returnValueMap(array(
                array('xpath', $xpath, $xpath),
                array('css', $css, $css),
            )));

        $this->assertEquals(3, count($this->document->findAll('xpath', $xpath)));
        $this->assertEquals(2, count($this->document->findAll('css', $css)));
    }

    public function testFind()
    {
        $this->driver
            ->expects($this->exactly(3))
            ->method('find')
            ->with('//html/h3[a]')
            ->will($this->onConsecutiveCalls(array(2, 3, 4), array(1, 2), array()));

        $xpath = 'h3[a]';
        $css = 'h3 > a';

        $this->selectors
            ->expects($this->exactly(3))
            ->method('selectorToXpath')
            ->will($this->returnValueMap(array(
                array('xpath', $xpath, $xpath),
                array('xpath', $xpath, $xpath),
                array('css', $css, $xpath),
            )));

        $this->assertEquals(2, $this->document->find('xpath', $xpath));
        $this->assertEquals(1, $this->document->find('css', $css));
        $this->assertNull($this->document->find('xpath', $xpath));
    }

    public function testFindField()
    {
        $this->mockNamedFinder(
            '//field',
            array('field1', 'field2', 'field3'),
            array('field', 'some field')
        );

        $this->assertEquals('field1', $this->document->findField('some field'));
        $this->assertEquals(null, $this->document->findField('some field'));
    }

    public function testFindLink()
    {
        $this->mockNamedFinder(
            '//link',
            array('link1', 'link2', 'link3'),
            array('link', 'some link')
        );

        $this->assertEquals('link1', $this->document->findLink('some link'));
        $this->assertEquals(null, $this->document->findLink('some link'));
    }

    public function testFindButton()
    {
        $this->mockNamedFinder(
            '//button',
            array('button1', 'button2', 'button3'),
            array('button', 'some button')
        );

        $this->assertEquals('button1', $this->document->findButton('some button'));
        $this->assertEquals(null, $this->document->findButton('some button'));
    }

    public function testFindById()
    {
        $xpath = '//*[@id=some-item-2]';

        $this->mockNamedFinder($xpath, array(array('id2', 'id3'), array()), array('id', 'some-item-2'));

        $this->assertEquals('id2', $this->document->findById('some-item-2'));
        $this->assertEquals(null, $this->document->findById('some-item-2'));
    }

    public function testHasSelector()
    {
        $this->driver
            ->expects($this->exactly(2))
            ->method('find')
            ->with('//html/some xpath')
            ->will($this->onConsecutiveCalls(array('id2', 'id3'), array()));

        $this->selectors
            ->expects($this->exactly(2))
            ->method('selectorToXpath')
            ->with('xpath', 'some xpath')
            ->will($this->returnValue('some xpath'));

        $this->assertTrue($this->document->has('xpath', 'some xpath'));
        $this->assertFalse($this->document->has('xpath', 'some xpath'));
    }

    public function testHasContent()
    {
        $this->mockNamedFinder(
            '//some content',
            array('item1', 'item2'),
            array('content', 'some content')
        );

        $this->assertTrue($this->document->hasContent('some content'));
        $this->assertFalse($this->document->hasContent('some content'));
    }

    public function testHasLink()
    {
        $this->mockNamedFinder(
            '//link',
            array('link1', 'link2', 'link3'),
            array('link', 'some link')
        );

        $this->assertTrue($this->document->hasLink('some link'));
        $this->assertFalse($this->document->hasLink('some link'));
    }

    public function testHasButton()
    {
        $this->mockNamedFinder(
            '//button',
            array('button1', 'button2', 'button3'),
            array('button', 'some button')
        );

        $this->assertTrue($this->document->hasButton('some button'));
        $this->assertFalse($this->document->hasButton('some button'));
    }

    public function testHasField()
    {
        $this->mockNamedFinder(
            '//field',
            array('field1', 'field2', 'field3'),
            array('field', 'some field')
        );

        $this->assertTrue($this->document->hasField('some field'));
        $this->assertFalse($this->document->hasField('some field'));
    }

    public function testHasCheckedField()
    {
        $checkbox = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $checkbox
            ->expects($this->exactly(2))
            ->method('isChecked')
            ->will($this->onConsecutiveCalls(true, false));

        $this->mockNamedFinder(
            '//field',
            array(array($checkbox), array(), array($checkbox)),
            array('field', 'some checkbox'),
            3
        );

        $this->assertTrue($this->document->hasCheckedField('some checkbox'));
        $this->assertFalse($this->document->hasCheckedField('some checkbox'));
        $this->assertFalse($this->document->hasCheckedField('some checkbox'));
    }

    public function testHasUncheckedField()
    {
        $checkbox = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $checkbox
            ->expects($this->exactly(2))
            ->method('isChecked')
            ->will($this->onConsecutiveCalls(true, false));

        $this->mockNamedFinder(
            '//field',
            array(array($checkbox), array(), array($checkbox)),
            array('field', 'some checkbox'),
            3
        );

        $this->assertFalse($this->document->hasUncheckedField('some checkbox'));
        $this->assertFalse($this->document->hasUncheckedField('some checkbox'));
        $this->assertTrue($this->document->hasUncheckedField('some checkbox'));
    }

    public function testHasSelect()
    {
        $this->mockNamedFinder(
            '//select',
            array('select'),
            array('select', 'some select field')
        );

        $this->assertTrue($this->document->hasSelect('some select field'));
        $this->assertFalse($this->document->hasSelect('some select field'));
    }

    public function testHasTable()
    {
        $this->mockNamedFinder(
            '//table',
            array('table'),
            array('table', 'some table')
        );

        $this->assertTrue($this->document->hasTable('some table'));
        $this->assertFalse($this->document->hasTable('some table'));
    }

    public function testClickLink()
    {
        $node = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects($this->once())
            ->method('click');

        $this->mockNamedFinder(
            '//link',
            array($node),
            array('link', 'some link')
        );

        $this->document->clickLink('some link');
        $this->setExpectedException('Behat\Mink\Exception\ElementNotFoundException');
        $this->document->clickLink('some link');
    }

    public function testClickButton()
    {
        $node = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects($this->once())
            ->method('press');

        $this->mockNamedFinder(
            '//button',
            array($node),
            array('button', 'some button')
        );

        $this->document->pressButton('some button');
        $this->setExpectedException('Behat\Mink\Exception\ElementNotFoundException');
        $this->document->pressButton('some button');
    }

    public function testFillField()
    {
        $node = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects($this->once())
            ->method('setValue')
            ->with('some val');

        $this->mockNamedFinder(
            '//field',
            array($node),
            array('field', 'some field')
        );

        $this->document->fillField('some field', 'some val');
        $this->setExpectedException('Behat\Mink\Exception\ElementNotFoundException');
        $this->document->fillField('some field', 'some val');
    }

    public function testCheckField()
    {
        $node = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects($this->once())
            ->method('check');

        $this->mockNamedFinder(
            '//field',
            array($node),
            array('field', 'some field')
        );

        $this->document->checkField('some field');
        $this->setExpectedException('Behat\Mink\Exception\ElementNotFoundException');
        $this->document->checkField('some field');
    }

    public function testUncheckField()
    {
        $node = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects($this->once())
            ->method('uncheck');

        $this->mockNamedFinder(
            '//field',
            array($node),
            array('field', 'some field')
        );

        $this->document->uncheckField('some field');
        $this->setExpectedException('Behat\Mink\Exception\ElementNotFoundException');
        $this->document->uncheckField('some field');
    }

    public function testSelectField()
    {
        $node = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects($this->once())
            ->method('selectOption')
            ->with('option2');

        $this->mockNamedFinder(
            '//field',
            array($node),
            array('field', 'some field')
        );

        $this->document->selectFieldOption('some field', 'option2');
        $this->setExpectedException('Behat\Mink\Exception\ElementNotFoundException');
        $this->document->selectFieldOption('some field', 'option2');
    }

    public function testAttachFileToField()
    {
        $node = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects($this->once())
            ->method('attachFile')
            ->with('/path/to/file');

        $this->mockNamedFinder(
            '//field',
            array($node),
            array('field', 'some field')
        );

        $this->document->attachFileToField('some field', '/path/to/file');
        $this->setExpectedException('Behat\Mink\Exception\ElementNotFoundException');
        $this->document->attachFileToField('some field', '/path/to/file');
    }

    public function testGetContent()
    {
        $expects = 'page content';
        $this->driver
            ->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($expects));

        $this->assertEquals($expects, $this->document->getContent());
    }

    public function testGetText()
    {
        $expects = 'val1';
        $this->driver
            ->expects($this->once())
            ->method('getText')
            ->with('//html')
            ->will($this->returnValue($expects));

        $this->assertEquals($expects, $this->document->getText());
    }

    public function testGetHtml()
    {
        $expects = 'val1';
        $this->driver
            ->expects($this->once())
            ->method('getHtml')
            ->with('//html')
            ->will($this->returnValue($expects));

        $this->assertEquals($expects, $this->document->getHtml());
    }

    public function testGetOuterHtml()
    {
        $expects = 'val1';
        $this->driver
            ->expects($this->once())
            ->method('getOuterHtml')
            ->with('//html')
            ->will($this->returnValue($expects));

        $this->assertEquals($expects, $this->document->getOuterHtml());
    }
}
