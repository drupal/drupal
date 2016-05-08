<?php

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;

/**
 * Adds and configures custom languages.
 *
 * @group language
 */
class LanguageCustomLanguageConfigurationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * Functional tests for adding, editing and deleting languages.
   */
  public function testLanguageConfiguration() {

    // Create user with permissions to add and remove languages.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Add custom language.
    $edit = array(
      'predefined_langcode' => 'custom',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Test validation on missing values.
    $this->assertText(t('@name field is required.', array('@name' => t('Language code'))));
    $this->assertText(t('@name field is required.', array('@name' => t('Language name'))));
    $empty_language = new Language();
    $this->assertFieldChecked('edit-direction-' . $empty_language->getDirection(), 'Consistent usage of language direction.');
    $this->assertUrl(\Drupal::url('language.add', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');

    // Test validation of invalid values.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'white space',
      'label' => '<strong>evil markup</strong>',
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    $this->assertRaw(t('%field must be a valid language tag as <a href=":url">defined by the W3C</a>.', array(
      '%field' => t('Language code'),
      ':url' => 'http://www.w3.org/International/articles/language-tags/',
    )));

    $this->assertRaw(t('%field cannot contain any markup.', array('%field' => t('Language name'))));
    $this->assertUrl(\Drupal::url('language.add', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');

    // Test adding a custom language with a numeric region code.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'es-419',
      'label' => 'Latin American Spanish',
      'direction' => LanguageInterface::DIRECTION_LTR,
    );

    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t(
      'The language %language has been created and can now be used.',
      array('%language' => $edit['label'])
    ));
    $this->assertUrl(\Drupal::url('entity.configurable_language.collection', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');

    // Test validation of existing language values.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'de',
      'label' => 'German',
      'direction' => LanguageInterface::DIRECTION_LTR,
    );

    // Add the language the first time.
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t(
      'The language %language has been created and can now be used.',
      array('%language' => $edit['label'])
    ));
    $this->assertUrl(\Drupal::url('entity.configurable_language.collection', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');

    // Add the language a second time and confirm that this is not allowed.
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t(
      'The language %language (%langcode) already exists.',
      array('%language' => $edit['label'], '%langcode' => $edit['langcode'])
    ));
    $this->assertUrl(\Drupal::url('language.add', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');
  }

}
