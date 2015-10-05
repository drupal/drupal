<?php

namespace Behat\Mink\Tests\Driver\Form;

use Behat\Mink\Tests\Driver\TestCase;

class CheckboxTest extends TestCase
{
    public function testManipulate()
    {
        $this->getSession()->visit($this->pathTo('advanced_form.html'));

        $checkbox = $this->getAssertSession()->fieldExists('agreement');

        $this->assertNull($checkbox->getValue());
        $this->assertFalse($checkbox->isChecked());

        $checkbox->check();

        $this->assertEquals('yes', $checkbox->getValue());
        $this->assertTrue($checkbox->isChecked());

        $checkbox->uncheck();

        $this->assertNull($checkbox->getValue());
        $this->assertFalse($checkbox->isChecked());
    }

    public function testSetValue()
    {
        $this->getSession()->visit($this->pathTo('advanced_form.html'));

        $checkbox = $this->getAssertSession()->fieldExists('agreement');

        $this->assertNull($checkbox->getValue());
        $this->assertFalse($checkbox->isChecked());

        $checkbox->setValue(true);

        $this->assertEquals('yes', $checkbox->getValue());
        $this->assertTrue($checkbox->isChecked());

        $checkbox->setValue(false);

        $this->assertNull($checkbox->getValue());
        $this->assertFalse($checkbox->isChecked());
    }

    public function testCheckboxMultiple()
    {
        $this->getSession()->visit($this->pathTo('/multicheckbox_form.html'));
        $webAssert = $this->getAssertSession();

        $this->assertEquals('Multicheckbox Test', $webAssert->elementExists('css', 'h1')->getText());

        $updateMail = $webAssert->elementExists('css', '[name="mail_types[]"][value="update"]');
        $spamMail = $webAssert->elementExists('css', '[name="mail_types[]"][value="spam"]');

        $this->assertEquals('update', $updateMail->getValue());
        $this->assertNull($spamMail->getValue());

        $this->assertTrue($updateMail->isChecked());
        $this->assertFalse($spamMail->isChecked());

        $updateMail->uncheck();
        $this->assertFalse($updateMail->isChecked());
        $this->assertFalse($spamMail->isChecked());

        $spamMail->check();
        $this->assertFalse($updateMail->isChecked());
        $this->assertTrue($spamMail->isChecked());
    }
}
