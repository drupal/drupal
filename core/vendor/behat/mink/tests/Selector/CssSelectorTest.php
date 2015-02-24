<?php

namespace Behat\Mink\Tests\Selector;

use Behat\Mink\Selector\CssSelector;

class CssSelectorTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Symfony\Component\CssSelector\CssSelector')) {
            $this->markTestSkipped('Symfony2 CssSelector component not installed');
        }
    }

    public function testSelector()
    {
        $selector = new CssSelector();

        $this->assertEquals('descendant-or-self::h3', $selector->translateToXPath('h3'));
        $this->assertEquals('descendant-or-self::h3/span', $selector->translateToXPath('h3 > span'));

        if (interface_exists('Symfony\Component\CssSelector\XPath\TranslatorInterface')) {
            // The rewritten component of Symfony 2.3 checks for attribute existence first for the class.
            $expectation = "descendant-or-self::h3/*[@class and contains(concat(' ', normalize-space(@class), ' '), ' my_div ')]";
        } else {
            $expectation = "descendant-or-self::h3/*[contains(concat(' ', normalize-space(@class), ' '), ' my_div ')]";
        }
        $this->assertEquals($expectation, $selector->translateToXPath('h3 > .my_div'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsForArrayLocator()
    {
        $selector = new CssSelector();

        $selector->translateToXPath(array('h3'));
    }
}
