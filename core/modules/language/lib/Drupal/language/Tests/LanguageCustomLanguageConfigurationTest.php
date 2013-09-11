<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageCustomConfigurationTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;

/**
 * Functional tests for language configuration.
 */
class LanguageCustomLanguageConfigurationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  public static function getInfo() {
    return array(
      'name' => 'Custom Language configuration',
      'description' => 'Adds and configures custom languages.',
      'group' => 'Language',
    );
  }

  /**
   * Functional tests for adding, editing and deleting languages.
   */
  public function testLanguageConfiguration() {
    global $base_url;

    // Create user with permissions to add and remove languages.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Add custom language.
    $edit = array(
      'predefined_langcode' => 'custom',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Test validation on missing values.
    $this->assertText(t('!name field is required.', array('!name' => t('Language code'))));
    $this->assertText(t('!name field is required.', array('!name' => t('Language name in English'))));
    $empty_language = new Language();
    $this->assertFieldChecked('edit-direction-' . $empty_language->direction, 'Consistent usage of language direction.');
    $this->assertEqual($this->getUrl(), url('admin/config/regional/language/add', array('absolute' => TRUE)), 'Correct page redirection.');

    // Test validation of invalid values.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'white space',
      'name' => '<strong>evil markup</strong>',
      'direction' => Language::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t('%field may only contain characters a-z, underscores, or hyphens.', array('%field' => t('Language code'))));
    $this->assertRaw(t('%field cannot contain any markup.', array('%field' => t('Language name in English'))));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/language/add', array('absolute' => TRUE)), 'Correct page redirection.');

    // Test validation of existing language values.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'de',
      'name' => 'German',
      'direction' => Language::DIRECTION_LTR,
    );

    // Add the language the first time.
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t(
      'The language %language has been created and can now be used.',
      array('%language' => $edit['name'])
    ));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/language', array('absolute' => TRUE)), 'Correct page redirection.');

    // Add the language a second time and confirm that this is not allowed.
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t(
      'The language %language (%langcode) already exists.',
      array('%language' => $edit['name'], '%langcode' => $edit['langcode'])
    ));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/language/add', array('absolute' => TRUE)), 'Correct page redirection.');
  }
}
