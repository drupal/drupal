<?php

namespace Drupal\system\Tests\Form;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the form API email element.
 *
 * @group Form
 */
class EmailTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  protected $profile = 'testing';

  /**
   * Tests that #type 'email' fields are properly validated.
   */
  function testFormEmail() {
    $edit = array();
    $edit['email'] = 'invalid';
    $edit['email_required'] = ' ';
    $this->drupalPostForm('form-test/email', $edit, 'Submit');
    $this->assertRaw(t('The email address %mail is not valid.', array('%mail' => 'invalid')));
    $this->assertRaw(t('@name field is required.', array('@name' => 'Address')));

    $edit = array();
    $edit['email_required'] = '  foo.bar@example.com ';
    $values = Json::decode($this->drupalPostForm('form-test/email', $edit, 'Submit'));
    $this->assertIdentical($values['email'], '');
    $this->assertEqual($values['email_required'], 'foo.bar@example.com');

    $edit = array();
    $edit['email'] = 'foo@example.com';
    $edit['email_required'] = 'example@drupal.org';
    $values = Json::decode($this->drupalPostForm('form-test/email', $edit, 'Submit'));
    $this->assertEqual($values['email'], 'foo@example.com');
    $this->assertEqual($values['email_required'], 'example@drupal.org');
  }

}
