<?php

namespace Behat\Mink\Tests\Element;

use Behat\Mink\Element\NodeElement;

class NodeElementTest extends ElementTest
{
    public function testGetXpath()
    {
        $node = new NodeElement('some custom xpath', $this->session);

        $this->assertEquals('some custom xpath', $node->getXpath());
        $this->assertNotEquals('not some custom xpath', $node->getXpath());
    }

    public function testGetText()
    {
        $expected = 'val1';
        $node = new NodeElement('text_tag', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('getText')
            ->with('text_tag')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $node->getText());
    }

    public function testGetOuterHtml()
    {
        $expected = 'val1';
        $node = new NodeElement('text_tag', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('getOuterHtml')
            ->with('text_tag')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $node->getOuterHtml());
    }

    public function testElementIsValid()
    {
        $elementXpath = 'some xpath';
        $node = new NodeElement($elementXpath, $this->session);

        $this->driver
            ->expects($this->once())
            ->method('find')
            ->with($elementXpath)
            ->will($this->returnValue(array($elementXpath)));

        $this->assertTrue($node->isValid());
    }

    public function testElementIsNotValid()
    {
        $node = new NodeElement('some xpath', $this->session);

        $this->driver
            ->expects($this->exactly(2))
            ->method('find')
            ->with('some xpath')
            ->will($this->onConsecutiveCalls(array(), array('xpath1', 'xpath2')));

        $this->assertFalse($node->isValid(), 'no elements found is invalid element');
        $this->assertFalse($node->isValid(), 'more then 1 element found is invalid element');
    }

    public function testWaitForSuccess()
    {
        $callCounter = 0;
        $node = new NodeElement('some xpath', $this->session);

        $result = $node->waitFor(5, function ($givenNode) use (&$callCounter) {
            ++$callCounter;

            if (1 === $callCounter) {
                return null;
            } elseif (2 === $callCounter) {
                return false;
            } elseif (3 === $callCounter) {
                return array();
            }

            return $givenNode;
        });

        $this->assertEquals(4, $callCounter, '->waitFor() tries to locate element several times before failing');
        $this->assertSame($node, $result, '->waitFor() returns node found in callback');
    }

    /**
     * @medium
     */
    public function testWaitForTimeout()
    {
        $node = new NodeElement('some xpath', $this->session);

        $expectedTimeout = 2;
        $startTime = microtime(true);
        $result = $node->waitFor($expectedTimeout, function () {
            return null;
        });
        $endTime = microtime(true);

        $this->assertNull($result, '->waitFor() returns whatever callback gives');
        $this->assertEquals($expectedTimeout, round($endTime - $startTime));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWaitForFailure()
    {
        $node = new NodeElement('some xpath', $this->session);
        $node->waitFor(5, 'not a callable');
    }

    public function testHasAttribute()
    {
        $node = new NodeElement('input_tag', $this->session);

        $this->driver
            ->expects($this->exactly(2))
            ->method('getAttribute')
            ->with('input_tag', 'href')
            ->will($this->onConsecutiveCalls(null, 'http://...'));

        $this->assertFalse($node->hasAttribute('href'));
        $this->assertTrue($node->hasAttribute('href'));
    }

    public function testGetAttribute()
    {
        $expected = 'http://...';
        $node = new NodeElement('input_tag', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('getAttribute')
            ->with('input_tag', 'href')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $node->getAttribute('href'));
    }

    public function testHasClass()
    {
        $node = new NodeElement('input_tag', $this->session);

        $this->driver
            ->expects($this->exactly(6))
            ->method('getAttribute')
            ->with('input_tag', 'class')
            ->will($this->returnValue('
            class1  class2
            '));

        $this->assertTrue($node->hasClass('class1'), 'The "class1" was found');
        $this->assertTrue($node->hasClass('class2'), 'The "class2" was found');
        $this->assertFalse($node->hasClass('class3'), 'The "class3" was not found');
    }

    public function testHasClassWithoutArgument()
    {
        $node = new NodeElement('input_tag', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('getAttribute')
            ->with('input_tag', 'class')
            ->will($this->returnValue(null));

        $this->assertFalse($node->hasClass('class3'));
    }

    public function testGetValue()
    {
        $expected = 'val1';
        $node = new NodeElement('input_tag', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('getValue')
            ->with('input_tag')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $node->getValue());
    }

    public function testSetValue()
    {
        $expected = 'new_val';
        $node = new NodeElement('input_tag', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('setValue')
            ->with('input_tag', $expected);

        $node->setValue($expected);
    }

    public function testClick()
    {
        $node = new NodeElement('link_or_button', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('click')
            ->with('link_or_button');

        $node->click();
    }

    public function testPress()
    {
        $node = new NodeElement('link_or_button', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('click')
            ->with('link_or_button');

        $node->press();
    }

    public function testRightClick()
    {
        $node = new NodeElement('elem', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('rightClick')
            ->with('elem');

        $node->rightClick();
    }

    public function testDoubleClick()
    {
        $node = new NodeElement('elem', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('doubleClick')
            ->with('elem');

        $node->doubleClick();
    }

    public function testCheck()
    {
        $node = new NodeElement('checkbox_or_radio', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('check')
            ->with('checkbox_or_radio');

        $node->check();
    }

    public function testUncheck()
    {
        $node = new NodeElement('checkbox_or_radio', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('uncheck')
            ->with('checkbox_or_radio');

        $node->uncheck();
    }

    public function testSelectOption()
    {
        $node = new NodeElement('select', $this->session);
        $option = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();
        $option
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('item1'));

        $this->driver
            ->expects($this->once())
            ->method('getTagName')
            ->with('select')
            ->will($this->returnValue('select'));

        $this->driver
            ->expects($this->once())
            ->method('find')
            ->with('select/option')
            ->will($this->returnValue(array($option)));

        $this->selectors
            ->expects($this->once())
            ->method('selectorToXpath')
            ->with('named_exact', array('option', 'item1'))
            ->will($this->returnValue('option'));

        $this->driver
            ->expects($this->once())
            ->method('selectOption')
            ->with('select', 'item1', false);

        $node->selectOption('item1');
    }

    /**
     * @expectedException \Behat\Mink\Exception\ElementNotFoundException
     */
    public function testSelectOptionNotFound()
    {
        $node = new NodeElement('select', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('getTagName')
            ->with('select')
            ->will($this->returnValue('select'));

        $this->driver
            ->expects($this->exactly(2))
            ->method('find')
            ->with('select/option')
            ->will($this->returnValue(array()));

        $this->selectors
            ->expects($this->exactly(2))
            ->method('selectorToXpath')
            ->with($this->logicalOr('named_exact', 'named_partial'), array('option', 'item1'))
            ->will($this->returnValue('option'));

        $node->selectOption('item1');
    }

    public function testSelectOptionOtherTag()
    {
        $node = new NodeElement('div', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('getTagName')
            ->with('div')
            ->will($this->returnValue('div'));

        $this->driver
            ->expects($this->once())
            ->method('selectOption')
            ->with('div', 'item1', false);

        $node->selectOption('item1');
    }

    public function testGetTagName()
    {
        $node = new NodeElement('html//h3', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('getTagName')
            ->with('html//h3')
            ->will($this->returnValue('h3'));

        $this->assertEquals('h3', $node->getTagName());
    }

    public function testGetParent()
    {
        $node = new NodeElement('elem', $this->session);
        $parent = $this->getMockBuilder('Behat\Mink\Element\NodeElement')
            ->disableOriginalConstructor()
            ->getMock();

        $this->driver
            ->expects($this->once())
            ->method('find')
            ->with('elem/..')
            ->will($this->returnValue(array($parent)));

        $this->selectors
            ->expects($this->once())
            ->method('selectorToXpath')
            ->with('xpath', '..')
            ->will($this->returnValue('..'));

        $this->assertSame($parent, $node->getParent());
    }

    public function testAttachFile()
    {
        $node = new NodeElement('elem', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('attachFile')
            ->with('elem', 'path');

        $node->attachFile('path');
    }

    public function testIsVisible()
    {
        $node = new NodeElement('some_xpath', $this->session);

        $this->driver
            ->expects($this->exactly(2))
            ->method('isVisible')
            ->with('some_xpath')
            ->will($this->onConsecutiveCalls(true, false));

        $this->assertTrue($node->isVisible());
        $this->assertFalse($node->isVisible());
    }

    public function testIsChecked()
    {
        $node = new NodeElement('some_xpath', $this->session);

        $this->driver
            ->expects($this->exactly(2))
            ->method('isChecked')
            ->with('some_xpath')
            ->will($this->onConsecutiveCalls(true, false));

        $this->assertTrue($node->isChecked());
        $this->assertFalse($node->isChecked());
    }

    public function testIsSelected()
    {
        $node = new NodeElement('some_xpath', $this->session);

        $this->driver
            ->expects($this->exactly(2))
            ->method('isSelected')
            ->with('some_xpath')
            ->will($this->onConsecutiveCalls(true, false));

        $this->assertTrue($node->isSelected());
        $this->assertFalse($node->isSelected());
    }

    public function testFocus()
    {
        $node = new NodeElement('some-element', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('focus')
            ->with('some-element');

        $node->focus();
    }

    public function testBlur()
    {
        $node = new NodeElement('some-element', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('blur')
            ->with('some-element');

        $node->blur();
    }

    public function testMouseOver()
    {
        $node = new NodeElement('some-element', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('mouseOver')
            ->with('some-element');

        $node->mouseOver();
    }

    public function testDragTo()
    {
        $node = new NodeElement('some_tag1', $this->session);

        $target = $this->getMock('Behat\Mink\Element\ElementInterface');
        $target->expects($this->any())
            ->method('getXPath')
            ->will($this->returnValue('some_tag2'));

        $this->driver
            ->expects($this->once())
            ->method('dragTo')
            ->with('some_tag1', 'some_tag2');

        $node->dragTo($target);
    }

    public function testKeyPress()
    {
        $node = new NodeElement('elem', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('keyPress')
            ->with('elem', 'key');

        $node->keyPress('key');
    }

    public function testKeyDown()
    {
        $node = new NodeElement('elem', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('keyDown')
            ->with('elem', 'key');

        $node->keyDown('key');
    }

    public function testKeyUp()
    {
        $node = new NodeElement('elem', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('keyUp')
            ->with('elem', 'key');

        $node->keyUp('key');
    }

    public function testSubmitForm()
    {
        $node = new NodeElement('some_xpath', $this->session);

        $this->driver
            ->expects($this->once())
            ->method('submitForm')
            ->with('some_xpath');

        $node->submit();
    }

    public function testFindAllUnion()
    {
        $node = new NodeElement('some_xpath', $this->session);
        $xpath = "some_tag1 | some_tag2[@foo =\n 'bar|'']\n | some_tag3[foo | bar]";
        $expected = "some_xpath/some_tag1 | some_xpath/some_tag2[@foo =\n 'bar|''] | some_xpath/some_tag3[foo | bar]";

        $this->driver
            ->expects($this->exactly(1))
            ->method('find')
            ->will($this->returnValueMap(array(
                array($expected, array(2, 3, 4)),
            )));

        $this->selectors
            ->expects($this->exactly(1))
            ->method('selectorToXpath')
            ->will($this->returnValueMap(array(
                array('xpath', $xpath, $xpath),
            )));

        $this->assertEquals(3, count($node->findAll('xpath', $xpath)));
    }

    public function testFindAllParentUnion()
    {
        $node = new NodeElement('some_xpath | another_xpath', $this->session);
        $xpath = 'some_tag1 | some_tag2';
        $expectedPrefixed = '(some_xpath | another_xpath)/some_tag1 | (some_xpath | another_xpath)/some_tag2';

        $this->driver
            ->expects($this->exactly(1))
            ->method('find')
            ->will($this->returnValueMap(array(
                array($expectedPrefixed, array(2, 3, 4)),
            )));

        $this->selectors
            ->expects($this->exactly(1))
            ->method('selectorToXpath')
            ->will($this->returnValueMap(array(
                array('xpath', $xpath, $xpath),
            )));

        $this->assertEquals(3, count($node->findAll('xpath', $xpath)));
    }
}
