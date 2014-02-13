<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserValidateCurrentPassCustomFormTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests user_validate_current_pass on a custom form.
 */
class UserValidateCurrentPassCustomFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user_form_test');

  public static function getInfo() {
    return array(
      'name' => 'User validate current pass custom form',
      'description' => 'Test that user_validate_current_pass is usable on a custom form.',
      'group' => 'User',
    );
  }

  /**
   * User with permission to view content.
   */
  protected $accessUser;

  /**
   * User permission to administer users.
   */
  protected $adminUser;

  function setUp() {
    parent::setUp();

    // Create two users
    $this->accessUser = $this->drupalCreateUser(array());
    $this->adminUser = $this->drupalCreateUser(array('administer users'));
  }

  /**
   * Tests that user_validate_current_pass can be reused on a custom form.
   */
  function testUserValidateCurrentPassCustomForm() {
    $this->drupalLogin($this->adminUser);

    // Submit the custom form with the admin user using the access user's password.
    $edit = array();
    $edit['user_form_test_field'] = $this->accessUser->getUsername();
    $edit['current_pass'] = $this->accessUser->pass_raw;
    $this->drupalPostForm('user_form_test_current_password/' . $this->accessUser->id(), $edit, t('Test'));
    $this->assertText(t('The password has been validated and the form submitted successfully.'));
  }
}
