<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\EmailTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests email element.
 */
class EmailTest extends WebTestBase {
  protected $profile = 'testing';

  public static function getInfo() {
    return array(
      'name' => 'Form API email',
      'description' => 'Tests the form API email element.',
      'group' => 'Form API',
    );
  }

  function setUp() {
    parent::setUp('form_test');
  }

  /**
   * Tests that #type 'email' fields are properly validated.
   */
  function testFormEmail() {
    $edit = array();
    $edit['email'] = 'invalid';
    $edit['email_required'] = ' ';
    $this->drupalPost('form-test/email', $edit, 'Submit');
    $this->assertRaw(t('The e-mail address %mail is not valid.', array('%mail' => 'invalid')));
    $this->assertRaw(t('!name field is required.', array('!name' => 'Address')));

    $edit = array();
    $edit['email_required'] = '  foo.bar@example.com ';
    $values = drupal_json_decode($this->drupalPost('form-test/email', $edit, 'Submit'));
    $this->assertIdentical($values['email'], '');
    $this->assertEqual($values['email_required'], 'foo.bar@example.com');

    $edit = array();
    $edit['email'] = 'foo@example.com';
    $edit['email_required'] = 'example@drupal.org';
    $values = drupal_json_decode($this->drupalPost('form-test/email', $edit, 'Submit'));
    $this->assertEqual($values['email'], 'foo@example.com');
    $this->assertEqual($values['email_required'], 'example@drupal.org');
  }
}
