<?php

namespace Behat\Mink\Tests\Driver\Form;

use Behat\Mink\Tests\Driver\TestCase;

class GeneralTest extends TestCase
{
    // test multiple submit buttons
    public function testIssue212()
    {
        $session = $this->getSession();

        $session->visit($this->pathTo('/issue212.html'));

        $field = $this->findById('poney-button');
        $this->assertEquals('poney', $field->getValue());
    }

    public function testBasicForm()
    {
        $this->getSession()->visit($this->pathTo('/basic_form.html'));

        $webAssert = $this->getAssertSession();
        $page = $this->getSession()->getPage();
        $this->assertEquals('Basic Form Page', $webAssert->elementExists('css', 'h1')->getText());

        $firstname  = $webAssert->fieldExists('first_name');
        $lastname   = $webAssert->fieldExists('lastn');

        $this->assertEquals('Firstname', $firstname->getValue());
        $this->assertEquals('Lastname', $lastname->getValue());

        $firstname->setValue('Konstantin');
        $page->fillField('last_name', 'Kudryashov');

        $this->assertEquals('Konstantin', $firstname->getValue());
        $this->assertEquals('Kudryashov', $lastname->getValue());

        $page->findButton('Reset')->click();

        $this->assertEquals('Firstname', $firstname->getValue());
        $this->assertEquals('Lastname', $lastname->getValue());

        $firstname->setValue('Konstantin');
        $page->fillField('last_name', 'Kudryashov');

        $page->findButton('Save')->click();

        if ($this->safePageWait(5000, 'document.getElementById("first") !== null')) {
            $this->assertEquals('Anket for Konstantin', $webAssert->elementExists('css', 'h1')->getText());
            $this->assertEquals('Firstname: Konstantin', $webAssert->elementExists('css', '#first')->getText());
            $this->assertEquals('Lastname: Kudryashov', $webAssert->elementExists('css', '#last')->getText());
        }
    }

    /**
     * @dataProvider formSubmitWaysDataProvider
     */
    public function testFormSubmitWays($submitVia)
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/basic_form.html'));
        $page = $session->getPage();
        $webAssert = $this->getAssertSession();

        $firstname = $webAssert->fieldExists('first_name');
        $firstname->setValue('Konstantin');

        $page->findButton($submitVia)->click();

        if ($this->safePageWait(5000, 'document.getElementById("first") !== null')) {
            $this->assertEquals('Firstname: Konstantin', $webAssert->elementExists('css', '#first')->getText());
        } else {
            $this->fail('Form was never submitted');
        }
    }

    public function formSubmitWaysDataProvider()
    {
        return array(
            array('Save'),
            array('input-type-image'),
            array('button-without-type'),
            array('button-type-submit'),
        );
    }

    public function testFormSubmit()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/basic_form.html'));

        $webAssert = $this->getAssertSession();
        $webAssert->fieldExists('first_name')->setValue('Konstantin');

        $webAssert->elementExists('xpath', 'descendant-or-self::form[1]')->submit();

        if ($this->safePageWait(5000, 'document.getElementById("first") !== null')) {
            $this->assertEquals('Firstname: Konstantin', $webAssert->elementExists('css', '#first')->getText());
        };
    }

    public function testFormSubmitWithoutButton()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/form_without_button.html'));

        $webAssert = $this->getAssertSession();
        $webAssert->fieldExists('first_name')->setValue('Konstantin');

        $webAssert->elementExists('xpath', 'descendant-or-self::form[1]')->submit();

        if ($this->safePageWait(5000, 'document.getElementById("first") !== null')) {
            $this->assertEquals('Firstname: Konstantin', $webAssert->elementExists('css', '#first')->getText());
        };
    }

    public function testBasicGetForm()
    {
        $this->getSession()->visit($this->pathTo('/basic_get_form.php'));
        $webAssert = $this->getAssertSession();

        $page = $this->getSession()->getPage();
        $this->assertEquals('Basic Get Form Page', $webAssert->elementExists('css', 'h1')->getText());

        $search = $webAssert->fieldExists('q');
        $search->setValue('some#query');
        $page->pressButton('Find');

        $div = $webAssert->elementExists('css', 'div');
        $this->assertEquals('some#query', $div->getText());
    }

    public function testAdvancedForm()
    {
        $this->getSession()->visit($this->pathTo('/advanced_form.html'));
        $page = $this->getSession()->getPage();

        $page->fillField('first_name', 'ever');
        $page->fillField('last_name', 'zet');

        $page->pressButton('Register');

        $this->assertContains('no file', $page->getContent());

        $this->getSession()->visit($this->pathTo('/advanced_form.html'));

        $webAssert = $this->getAssertSession();
        $page = $this->getSession()->getPage();
        $this->assertEquals('ADvanced Form Page', $webAssert->elementExists('css', 'h1')->getText());

        $firstname   = $webAssert->fieldExists('first_name');
        $lastname    = $webAssert->fieldExists('lastn');
        $email       = $webAssert->fieldExists('Your email:');
        $select      = $webAssert->fieldExists('select_number');
        $sex         = $webAssert->fieldExists('sex');
        $maillist    = $webAssert->fieldExists('mail_list');
        $agreement   = $webAssert->fieldExists('agreement');
        $notes       = $webAssert->fieldExists('notes');
        $about       = $webAssert->fieldExists('about');

        $this->assertEquals('Firstname', $firstname->getValue());
        $this->assertEquals('Lastname', $lastname->getValue());
        $this->assertEquals('your@email.com', $email->getValue());
        $this->assertEquals('20', $select->getValue());
        $this->assertEquals('w', $sex->getValue());
        $this->assertEquals('original notes', $notes->getValue());

        $this->assertEquals('on', $maillist->getValue());
        $this->assertNull($agreement->getValue());

        $this->assertTrue($maillist->isChecked());
        $this->assertFalse($agreement->isChecked());

        $agreement->check();
        $this->assertTrue($agreement->isChecked());

        $maillist->uncheck();
        $this->assertFalse($maillist->isChecked());

        $select->selectOption('thirty');
        $this->assertEquals('30', $select->getValue());

        $sex->selectOption('m');
        $this->assertEquals('m', $sex->getValue());

        $notes->setValue('new notes');
        $this->assertEquals('new notes', $notes->getValue());

        $about->attachFile($this->mapRemoteFilePath(__DIR__.'/../../web-fixtures/some_file.txt'));

        $button = $page->findButton('Register');
        $this->assertNotNull($button);

        $page->fillField('first_name', 'Foo "item"');
        $page->fillField('last_name', 'Bar');
        $page->fillField('Your email:', 'ever.zet@gmail.com');

        $this->assertEquals('Foo "item"', $firstname->getValue());
        $this->assertEquals('Bar', $lastname->getValue());

        $button->press();

        if ($this->safePageWait(5000, 'document.getElementsByTagName("title") !== null')) {
            $out = <<<OUT
array (
  'agreement' = 'on',
  'email' = 'ever.zet@gmail.com',
  'first_name' = 'Foo "item"',
  'last_name' = 'Bar',
  'notes' = 'new notes',
  'select_number' = '30',
  'sex' = 'm',
  'submit' = 'Register',
)
some_file.txt
1 uploaded file
OUT;
            $this->assertContains($out, $page->getContent());
        }
    }

    public function testMultiInput()
    {
        $this->getSession()->visit($this->pathTo('/multi_input_form.html'));
        $page = $this->getSession()->getPage();
        $webAssert = $this->getAssertSession();
        $this->assertEquals('Multi input Test', $webAssert->elementExists('css', 'h1')->getText());

        $first = $webAssert->fieldExists('First');
        $second = $webAssert->fieldExists('Second');
        $third = $webAssert->fieldExists('Third');

        $this->assertEquals('tag1', $first->getValue());
        $this->assertSame('tag2', $second->getValue());
        $this->assertEquals('tag1', $third->getValue());

        $first->setValue('tag2');
        $this->assertEquals('tag2', $first->getValue());
        $this->assertSame('tag2', $second->getValue());
        $this->assertEquals('tag1', $third->getValue());

        $second->setValue('one');

        $this->assertEquals('tag2', $first->getValue());
        $this->assertSame('one', $second->getValue());

        $third->setValue('tag3');

        $this->assertEquals('tag2', $first->getValue());
        $this->assertSame('one', $second->getValue());
        $this->assertEquals('tag3', $third->getValue());

        $button = $page->findButton('Register');
        $this->assertNotNull($button);
        $button->press();

        $space = ' ';
        $out = <<<OUT
  'tags' =$space
  array (
    0 = 'tag2',
    1 = 'one',
    2 = 'tag3',
  ),
OUT;
        $this->assertContains($out, $page->getContent());
    }

    public function testAdvancedFormSecondSubmit()
    {
        $this->getSession()->visit($this->pathTo('/advanced_form.html'));
        $page = $this->getSession()->getPage();

        $button = $page->findButton('Login');
        $this->assertNotNull($button);
        $button->press();

        $toSearch = array(
            "'agreement' = 'off',",
            "'submit' = 'Login',",
            'no file',
        );

        $pageContent = $page->getContent();

        foreach ($toSearch as $searchString) {
            $this->assertContains($searchString, $pageContent);
        }
    }

    public function testSubmitEmptyTextarea()
    {
        $this->getSession()->visit($this->pathTo('/empty_textarea.html'));
        $page = $this->getSession()->getPage();

        $page->pressButton('Save');

        $toSearch = array(
            "'textarea' = '',",
            "'submit' = 'Save',",
            'no file',
        );

        $pageContent = $page->getContent();

        foreach ($toSearch as $searchString) {
            $this->assertContains($searchString, $pageContent);
        }
    }
}
