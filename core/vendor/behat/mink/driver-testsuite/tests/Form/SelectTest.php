<?php

namespace Behat\Mink\Tests\Driver\Form;

use Behat\Mink\Tests\Driver\TestCase;

class SelectTest extends TestCase
{
    public function testMultiselect()
    {
        $this->getSession()->visit($this->pathTo('/multiselect_form.html'));
        $webAssert = $this->getAssertSession();
        $page = $this->getSession()->getPage();
        $this->assertEquals('Multiselect Test', $webAssert->elementExists('css', 'h1')->getText());

        $select      = $webAssert->fieldExists('select_number');
        $multiSelect = $webAssert->fieldExists('select_multiple_numbers[]');
        $secondMultiSelect = $webAssert->fieldExists('select_multiple_values[]');

        $this->assertEquals('20', $select->getValue());
        $this->assertSame(array(), $multiSelect->getValue());
        $this->assertSame(array('2', '3'), $secondMultiSelect->getValue());

        $select->selectOption('thirty');
        $this->assertEquals('30', $select->getValue());

        $multiSelect->selectOption('one', true);

        $this->assertSame(array('1'), $multiSelect->getValue());

        $multiSelect->selectOption('three', true);

        $this->assertEquals(array('1', '3'), $multiSelect->getValue());

        $secondMultiSelect->selectOption('two');
        $this->assertSame(array('2'), $secondMultiSelect->getValue());

        $button = $page->findButton('Register');
        $this->assertNotNull($button);
        $button->press();

        $space = ' ';
        $out = <<<OUT
  'agreement' = 'off',
  'select_multiple_numbers' =$space
  array (
    0 = '1',
    1 = '3',
  ),
  'select_multiple_values' =$space
  array (
    0 = '2',
  ),
  'select_number' = '30',
OUT;
        $this->assertContains($out, $page->getContent());
    }

    /**
     * @dataProvider testElementSelectedStateCheckDataProvider
     */
    public function testElementSelectedStateCheck($selectName, $optionValue, $optionText)
    {
        $session = $this->getSession();
        $webAssert = $this->getAssertSession();
        $session->visit($this->pathTo('/multiselect_form.html'));
        $select = $webAssert->fieldExists($selectName);

        $optionValueEscaped = $session->getSelectorsHandler()->xpathLiteral($optionValue);
        $option = $webAssert->elementExists('named', array('option', $optionValueEscaped));

        $this->assertFalse($option->isSelected());
        $select->selectOption($optionText);
        $this->assertTrue($option->isSelected());
    }

    public function testElementSelectedStateCheckDataProvider()
    {
        return array(
            array('select_number', '30', 'thirty'),
            array('select_multiple_numbers[]', '2', 'two'),
        );
    }

    public function testSetValueSingleSelect()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/multiselect_form.html'));
        $select = $this->getAssertSession()->fieldExists('select_number');

        $select->setValue('10');
        $this->assertEquals('10', $select->getValue());
    }

    public function testSetValueMultiSelect()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/multiselect_form.html'));
        $select = $this->getAssertSession()->fieldExists('select_multiple_values[]');

        $select->setValue(array('1', '2'));
        $this->assertEquals(array('1', '2'), $select->getValue());
    }

    /**
     * @see https://github.com/Behat/Mink/issues/193
     */
    public function testOptionWithoutValue()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/issue193.html'));

        $session->getPage()->selectFieldOption('options-without-values', 'Two');
        $this->assertEquals('Two', $this->findById('options-without-values')->getValue());

        $this->assertTrue($this->findById('two')->isSelected());
        $this->assertFalse($this->findById('one')->isSelected());

        $session->getPage()->selectFieldOption('options-with-values', 'two');
        $this->assertEquals('two', $this->findById('options-with-values')->getValue());
    }

    /**
     * @see https://github.com/Behat/Mink/issues/131
     */
    public function testAccentuatedOption()
    {
        $this->getSession()->visit($this->pathTo('/issue131.html'));
        $page = $this->getSession()->getPage();

        $page->selectFieldOption('foobar', 'Gimme some accentués characters');

        $this->assertEquals('1', $page->findField('foobar')->getValue());
    }
}
