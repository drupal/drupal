<?php

namespace Behat\Mink\Tests\Driver\Form;

use Behat\Mink\Tests\Driver\TestCase;

class RadioTest extends TestCase
{
    protected function setUp()
    {
        $this->getSession()->visit($this->pathTo('radio.html'));
    }

    public function testIsChecked()
    {
        $option = $this->findById('first');
        $option2 = $this->findById('second');

        $this->assertTrue($option->isChecked());
        $this->assertFalse($option2->isChecked());

        $option2->selectOption('updated');

        $this->assertFalse($option->isChecked());
        $this->assertTrue($option2->isChecked());
    }

    public function testSelectOption()
    {
        $option = $this->findById('first');

        $this->assertEquals('set', $option->getValue());

        $option->selectOption('updated');

        $this->assertEquals('updated', $option->getValue());

        $option->selectOption('set');

        $this->assertEquals('set', $option->getValue());
    }

    public function testSetValue()
    {
        $option = $this->findById('first');

        $this->assertEquals('set', $option->getValue());

        $option->setValue('updated');

        $this->assertEquals('updated', $option->getValue());
        $this->assertFalse($option->isChecked());
    }

    public function testSameNameInMultipleForms()
    {
        $option1 = $this->findById('reused_form1');
        $option2 = $this->findById('reused_form2');

        $this->assertEquals('test2', $option1->getValue());
        $this->assertEquals('test3', $option2->getValue());

        $option1->selectOption('test');

        $this->assertEquals('test', $option1->getValue());
        $this->assertEquals('test3', $option2->getValue());
    }

    /**
     * @see https://github.com/Behat/MinkSahiDriver/issues/32
     */
    public function testSetValueXPathEscaping()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/advanced_form.html'));
        $page = $session->getPage();

        $sex = $page->find('xpath', '//*[@name = "sex"]'."\n|\n".'//*[@id = "sex"]');
        $this->assertNotNull($sex, 'xpath with line ending works');

        $sex->setValue('m');
        $this->assertEquals('m', $sex->getValue(), 'no double xpath escaping during radio button value change');
    }
}
