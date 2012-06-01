<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserLanguageTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for a user's ability to change their default language.
 */
class UserLanguageTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User language settings',
      'description' => "Tests user's ability to change their default language.",
      'group' => 'User',
    );
  }

  function setUp() {
    parent::setUp(array('user', 'language'));
  }

  /**
   * Test if user can change their default language.
   */
  function testUserLanguageConfiguration() {
    global $base_url;

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    // User to change their default language.
    $web_user = $this->drupalCreateUser();

    // Add custom language.
    $this->drupalLogin($admin_user);
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language.
    $name = $this->randomName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->drupalLogout();

    // Login as normal user and edit account settings.
    $this->drupalLogin($web_user);
    $path = 'user/' . $web_user->uid . '/edit';
    $this->drupalGet($path);
    // Ensure language settings fieldset is available.
    $this->assertText(t('Language'), t('Language selector available.'));
    // Ensure custom language is present.
    $this->assertText($name, t('Language present on form.'));
    // Switch to our custom language.
    $edit = array(
      'preferred_langcode' => $langcode,
    );
    $this->drupalPost($path, $edit, t('Save'));
    // Ensure form was submitted successfully.
    $this->assertText(t('The changes have been saved.'), t('Changes were saved.'));
    // Check if language was changed.
    $elements = $this->xpath('//input[@id=:id]', array(':id' => 'edit-preferred-langcode-' . $langcode));
    $this->assertTrue(isset($elements[0]) && !empty($elements[0]['checked']), t('Default language successfully updated.'));

    $this->drupalLogout();
  }
}
