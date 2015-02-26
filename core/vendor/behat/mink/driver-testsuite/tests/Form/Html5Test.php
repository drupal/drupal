<?php

namespace Behat\Mink\Tests\Driver\Form;

use Behat\Mink\Tests\Driver\TestCase;

class Html5Test extends TestCase
{
    public function testHtml5FormInputAttribute()
    {
        $this->getSession()->visit($this->pathTo('/html5_form.html'));
        $page = $this->getSession()->getPage();
        $webAssert = $this->getAssertSession();

        $firstName = $webAssert->fieldExists('first_name');
        $lastName = $webAssert->fieldExists('last_name');

        $this->assertEquals('not set', $lastName->getValue());
        $firstName->setValue('John');
        $lastName->setValue('Doe');

        $this->assertEquals('Doe', $lastName->getValue());

        $page->pressButton('Submit in form');

        if ($this->safePageWait(5000, 'document.getElementsByTagName("title") !== null')) {
            $out = <<<OUT
  'first_name' = 'John',
  'last_name' = 'Doe',
OUT;
            $this->assertContains($out, $page->getContent());
            $this->assertNotContains('other_field', $page->getContent());
        }
    }

    public function testHtml5FormRadioAttribute()
    {
        $this->getSession()->visit($this->pathTo('html5_radio.html'));
        $page = $this->getSession()->getPage();

        $radio = $this->findById('sex_f');
        $otherRadio = $this->findById('sex_invalid');

        $this->assertEquals('f', $radio->getValue());
        $this->assertEquals('invalid', $otherRadio->getValue());

        $radio->selectOption('m');

        $this->assertEquals('m', $radio->getValue());
        $this->assertEquals('invalid', $otherRadio->getValue());

        $page->pressButton('Submit in form');

        $out = <<<OUT
  'sex' = 'm',
OUT;
        $this->assertContains($out, $page->getContent());
    }

    public function testHtml5FormButtonAttribute()
    {
        $this->getSession()->visit($this->pathTo('/html5_form.html'));
        $page = $this->getSession()->getPage();
        $webAssert = $this->getAssertSession();

        $firstName = $webAssert->fieldExists('first_name');
        $lastName = $webAssert->fieldExists('last_name');

        $firstName->setValue('John');
        $lastName->setValue('Doe');

        $page->pressButton('Submit outside form');

        if ($this->safePageWait(5000, 'document.getElementsByTagName("title") !== null')) {
            $out = <<<OUT
  'first_name' = 'John',
  'last_name' = 'Doe',
  'submit_button' = 'test',
OUT;
            $this->assertContains($out, $page->getContent());
        }
    }

    public function testHtml5FormOutside()
    {
        $this->getSession()->visit($this->pathTo('/html5_form.html'));
        $page = $this->getSession()->getPage();

        $page->fillField('other_field', 'hello');

        $page->pressButton('Submit separate form');

        if ($this->safePageWait(5000, 'document.getElementsByTagName("title") !== null')) {
            $out = <<<OUT
  'other_field' = 'hello',
OUT;
            $this->assertContains($out, $page->getContent());
            $this->assertNotContains('first_name', $page->getContent());
        }
    }

    public function testHtml5Types()
    {
        $this->getSession()->visit($this->pathTo('html5_types.html'));
        $page = $this->getSession()->getPage();

        $page->fillField('url', 'http://mink.behat.org/');
        $page->fillField('email', 'mink@example.org');
        $page->fillField('number', '6');
        $page->fillField('search', 'mink');
        $page->fillField('date', '2014-05-19');
        $page->fillField('color', '#ff00aa');

        $page->pressButton('Submit');

        $out = <<<OUT
  'color' = '#ff00aa',
  'date' = '2014-05-19',
  'email' = 'mink@example.org',
  'number' = '6',
  'search' = 'mink',
  'submit_button' = 'Submit',
  'url' = 'http://mink.behat.org/',
OUT;

        $this->assertContains($out, $page->getContent());
    }
}
