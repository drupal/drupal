<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the form API email element.
 *
 * @group Form
 */
class EmailTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that #type 'email' fields are properly validated.
   */
  public function testFormEmail() {
    $edit = [];
    $edit['email'] = 'invalid';
    $edit['email_required'] = ' ';
    $this->drupalPostForm('form-test/email', $edit, 'Submit');
    $this->assertRaw(t('The email address %mail is not valid.', ['%mail' => 'invalid']));
    $this->assertRaw(t('@name field is required.', ['@name' => 'Address']));

    $edit = [];
    $edit['email_required'] = '  foo.bar@example.com ';
    $this->drupalPostForm('form-test/email', $edit, 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertIdentical($values['email'], '');
    $this->assertEqual($values['email_required'], 'foo.bar@example.com');

    $edit = [];
    $edit['email'] = 'foo@example.com';
    $edit['email_required'] = 'example@drupal.org';
    $this->drupalPostForm('form-test/email', $edit, 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEqual($values['email'], 'foo@example.com');
    $this->assertEqual($values['email_required'], 'example@drupal.org');
  }

}
