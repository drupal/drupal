<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageConfigurationElementTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;

/**
 * Functional tests for language configuration's effect on negotiation setup.
 */
class LanguageConfigurationElementTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'language', 'language_elements_test');

  public static function getInfo() {
    return array(
      'name' => 'Language configuration form element tests',
      'description' => 'Tests the features of the language configuration element field.',
      'group' => 'Language',
    );
  }

  /**
   * Tests the language settings have been saved.
   */
  public function testLanguageConfigurationElement() {
    $this->drupalGet('language-tests/language_configuration_element');
    $edit['lang_configuration[langcode]'] = 'current_interface';
    $edit['lang_configuration[language_show]'] = FALSE;
    $this->drupalPostForm(NULL, $edit, 'Save');
    $lang_conf = language_get_default_configuration('some_custom_type', 'some_bundle');

    // Check that the settings have been saved.
    $this->assertEqual($lang_conf['langcode'], 'current_interface');
    $this->assertFalse($lang_conf['language_show']);
    $this->drupalGet('language-tests/language_configuration_element');
    $this->assertOptionSelected('edit-lang-configuration-langcode', 'current_interface');
    $this->assertNoFieldChecked('edit-lang-configuration-language-show');

    // Reload the page and save again.
    $this->drupalGet('language-tests/language_configuration_element');
    $edit['lang_configuration[langcode]'] = 'authors_default';
    $edit['lang_configuration[language_show]'] = TRUE;
    $this->drupalPostForm(NULL, $edit, 'Save');
    $lang_conf = language_get_default_configuration('some_custom_type', 'some_bundle');

    // Check that the settings have been saved.
    $this->assertEqual($lang_conf['langcode'], 'authors_default');
    $this->assertTrue($lang_conf['language_show']);
    $this->drupalGet('language-tests/language_configuration_element');
    $this->assertOptionSelected('edit-lang-configuration-langcode', 'authors_default');
    $this->assertFieldChecked('edit-lang-configuration-language-show');
  }

  /**
   * Tests that the language_get_default_langcode() returns the correct values.
   */
  public function testDefaultLangcode() {
    // Add some custom languages.
    foreach (array('aa', 'bb', 'cc') as $language_code) {
      $language = new Language(array(
        'id' => $language_code,
        'name' => $this->randomName(),
      ));
      language_save($language);
    }

    // Fixed language.
    language_save_default_configuration('custom_type', 'custom_bundle', array('langcode' => 'bb', 'language_show' => TRUE));
    $langcode = language_get_default_langcode('custom_type', 'custom_bundle');
    $this->assertEqual($langcode, 'bb');

    // Current interface.
    language_save_default_configuration('custom_type', 'custom_bundle', array('langcode' => 'current_interface', 'language_show' => TRUE));
    $langcode = language_get_default_langcode('custom_type', 'custom_bundle');
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $this->assertEqual($langcode, $language_interface->id);

    // Site's default.
    $old_default = \Drupal::languageManager()->getDefaultLanguage();
    // Ensure the language entity default value is correct.
    $language_entity = entity_load('language_entity', $old_default->getId());
    $this->assertTrue($language_entity->get('default'), 'The en language entity is flagged as the default language.');
    $old_default->default = FALSE;
    language_save($old_default);
    $new_default = \Drupal::languageManager()->getLanguage('cc');
    $new_default->default = TRUE;
    language_save($new_default);
    language_save_default_configuration('custom_type', 'custom_bundle', array('langcode' => 'site_default', 'language_show' => TRUE));
    $langcode = language_get_default_langcode('custom_type', 'custom_bundle');
    $this->assertEqual($langcode, 'cc');

    // Ensure the language entity default value is correct.
    $language_entity = entity_load('language_entity', $old_default->getId());
    $this->assertFalse($language_entity->get('default'), 'The en language entity is not flagged as the default language.');
    $language_entity = entity_load('language_entity', 'cc');
    // Check calling the Drupal\language\Entity\Language::isDefault() method
    // directly.
    $this->assertTrue($language_entity->isDefault(), 'The cc language entity is flagged as the default language.');

    // Check the default value of a language field when authors preferred option
    // is selected.
    // Create first an user and assign a preferred langcode to him.
    $some_user = $this->drupalCreateUser();
    $some_user->preferred_langcode = 'bb';
    $some_user->save();
    $this->drupalLogin($some_user);
    language_save_default_configuration('custom_type', 'some_bundle', array('langcode' => 'authors_default', 'language_show' => TRUE));
    $this->drupalGet('language-tests/language_configuration_element_test');
    $this->assertOptionSelected('edit-langcode', 'bb');
  }

  /**
   * Tests that the configuration is updated when the node type is changed.
   */
  public function testNodeTypeUpdate() {
    // Create the article content type first if the profile used is not the
    // standard one.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
    $admin_user = $this->drupalCreateUser(array('administer content types'));
    $this->drupalLogin($admin_user);
    $edit = array(
      'language_configuration[langcode]' => 'current_interface',
      'language_configuration[language_show]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    // Check the language default configuration for the articles.
    $configuration = language_get_default_configuration('node', 'article');
    $this->assertEqual($configuration, array('langcode' => 'current_interface', 'language_show' => TRUE), 'The default language configuration has been saved on the Article content type.');
    // Rename the article content type.
    $edit = array(
      'type' => 'article_2'
    );
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    // Check that we still have the settings for the new node type.
    $configuration = language_get_default_configuration('node', 'article_2');
    $this->assertEqual($configuration, array('langcode' => 'current_interface', 'language_show' => TRUE), 'The default language configuration has been kept on the new Article content type.');
  }
}
