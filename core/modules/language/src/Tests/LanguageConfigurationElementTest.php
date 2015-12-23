<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageConfigurationElementTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the features of the language configuration element field.
 *
 * @group language
 */
class LanguageConfigurationElementTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'node', 'language', 'language_elements_test', 'field_ui');

  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser(array('access administration pages', 'administer languages', 'administer content types'));
    $this->drupalLogin($user);
  }

  /**
   * Tests the language settings have been saved.
   */
  public function testLanguageConfigurationElement() {
    $this->drupalGet('language-tests/language_configuration_element');
    $edit['lang_configuration[langcode]'] = 'current_interface';
    $edit['lang_configuration[language_alterable]'] = FALSE;
    $this->drupalPostForm(NULL, $edit, 'Save');
    $lang_conf = ContentLanguageSettings::loadByEntityTypeBundle('entity_test', 'some_bundle');

    // Check that the settings have been saved.
    $this->assertEqual($lang_conf->getDefaultLangcode(), 'current_interface');
    $this->assertFalse($lang_conf->isLanguageAlterable());
    $this->drupalGet('language-tests/language_configuration_element');
    $this->assertOptionSelected('edit-lang-configuration-langcode', 'current_interface');
    $this->assertNoFieldChecked('edit-lang-configuration-language-alterable');

    // Reload the page and save again.
    $this->drupalGet('language-tests/language_configuration_element');
    $edit['lang_configuration[langcode]'] = 'authors_default';
    $edit['lang_configuration[language_alterable]'] = TRUE;
    $this->drupalPostForm(NULL, $edit, 'Save');
    $lang_conf = ContentLanguageSettings::loadByEntityTypeBundle('entity_test', 'some_bundle');

    // Check that the settings have been saved.
    $this->assertEqual($lang_conf->getDefaultLangcode(), 'authors_default');
    $this->assertTrue($lang_conf->isLanguageAlterable());
    $this->drupalGet('language-tests/language_configuration_element');
    $this->assertOptionSelected('edit-lang-configuration-langcode', 'authors_default');
    $this->assertFieldChecked('edit-lang-configuration-language-alterable');

    // Test if content type settings have been saved.
    $edit = array(
      'name' => 'Page',
      'type' => 'page',
      'language_configuration[langcode]' => 'authors_default',
      'language_configuration[language_alterable]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/add', $edit, 'Save and manage fields');

    // Make sure the settings are saved when creating the content type.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertOptionSelected('edit-language-configuration-langcode', 'authors_default');
    $this->assertFieldChecked('edit-language-configuration-language-alterable');

  }

  /**
   * Tests that the language_get_default_langcode() returns the correct values.
   */
  public function testDefaultLangcode() {
    // Add some custom languages.
    foreach (array('aa', 'bb', 'cc') as $language_code) {
      ConfigurableLanguage::create(array(
        'id' => $language_code,
        'label' => $this->randomMachineName(),
      ))->save();
    }

    // Fixed language.
    ContentLanguageSettings::loadByEntityTypeBundle('entity_test', 'custom_bundle')
      ->setLanguageAlterable(TRUE)
      ->setDefaultLangcode('bb')
      ->save();

    $langcode = language_get_default_langcode('entity_test', 'custom_bundle');
    $this->assertEqual($langcode, 'bb');

    // Current interface.
    ContentLanguageSettings::loadByEntityTypeBundle('entity_test', 'custom_bundle')
      ->setLanguageAlterable(TRUE)
      ->setDefaultLangcode('current_interface')
      ->save();

    $langcode = language_get_default_langcode('entity_test', 'custom_bundle');
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $this->assertEqual($langcode, $language_interface->getId());

    // Site's default.
    $old_default = \Drupal::languageManager()->getDefaultLanguage();
    // Ensure the language entity default value is correct.
    $configurable_language = entity_load('configurable_language', $old_default->getId());
    $this->assertTrue($configurable_language->isDefault(), 'The en language entity is flagged as the default language.');

    $this->config('system.site')->set('default_langcode', 'cc')->save();
    ContentLanguageSettings::loadByEntityTypeBundle('entity_test', 'custom_bundle')
      ->setLanguageAlterable(TRUE)
      ->setDefaultLangcode(LanguageInterface::LANGCODE_SITE_DEFAULT)
      ->save();
    $langcode = language_get_default_langcode('entity_test', 'custom_bundle');
    $this->assertEqual($langcode, 'cc');

    // Ensure the language entity default value is correct.
    $configurable_language = entity_load('configurable_language', $old_default->getId());
    $this->assertFalse($configurable_language->isDefault(), 'The en language entity is not flagged as the default language.');
    $configurable_language = entity_load('configurable_language', 'cc');
    // Check calling the
    // \Drupal\language\ConfigurableLanguageInterface::isDefault() method
    // directly.
    $this->assertTrue($configurable_language->isDefault(), 'The cc language entity is flagged as the default language.');

    // Check the default value of a language field when authors preferred option
    // is selected.
    // Create first an user and assign a preferred langcode to him.
    $some_user = $this->drupalCreateUser();
    $some_user->preferred_langcode = 'bb';
    $some_user->save();
    $this->drupalLogin($some_user);
    ContentLanguageSettings::create([
      'target_entity_type_id' => 'entity_test',
      'target_bundle' => 'some_bundle',
    ])->setLanguageAlterable(TRUE)
      ->setDefaultLangcode('authors_default')
      ->save();

    $this->drupalGet('language-tests/language_configuration_element_test');
    $this->assertOptionSelected('edit-langcode', 'bb');
  }

  /**
   * Tests that the configuration is retained when the node type is updated.
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
      'language_configuration[language_alterable]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    // Check the language default configuration for the articles.
    $configuration = ContentLanguageSettings::loadByEntityTypeBundle('node', 'article');
    $uuid = $configuration->uuid();
    $this->assertEqual($configuration->getDefaultLangcode(), 'current_interface', 'The default language configuration has been saved on the Article content type.');
    $this->assertTrue($configuration->isLanguageAlterable(), 'The alterable language configuration has been saved on the Article content type.');
    // Update the article content type by changing the title label.
    $edit = array(
      'title_label' => 'Name'
    );
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    // Check that we still have the settings for the updated node type.
    $configuration = ContentLanguageSettings::loadByEntityTypeBundle('node', 'article');
    $this->assertEqual($configuration->getDefaultLangcode(), 'current_interface', 'The default language configuration has been kept on the updated Article content type.');
    $this->assertTrue($configuration->isLanguageAlterable(), 'The alterable language configuration has been kept on the updated Article content type.');
    $this->assertEqual($configuration->uuid(), $uuid, 'The language configuration uuid has been kept on the updated Article content type.');
  }

  /**
   * Tests the language settings are deleted on bundle delete.
   */
  public function testNodeTypeDelete() {
    // Create the article content type first if the profile used is not the
    // standard one.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
        'type' => 'article',
        'name' => 'Article'
      ));
    }
    $admin_user = $this->drupalCreateUser(array('administer content types'));
    $this->drupalLogin($admin_user);

    // Create language configuration for the articles.
    $edit = array(
      'language_configuration[langcode]' => 'authors_default',
      'language_configuration[language_alterable]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));

    // Check the language default configuration for articles is present.
    $configuration = \Drupal::entityManager()->getStorage('language_content_settings')->load('node.article');
    $this->assertTrue($configuration, 'The language configuration is present.');

    // Delete 'article' bundle.
    $this->drupalPostForm('admin/structure/types/manage/article/delete', array(), t('Delete'));

    // Check that the language configuration has been deleted.
    \Drupal::entityManager()->getStorage('language_content_settings')->resetCache();
    $configuration = \Drupal::entityManager()->getStorage('language_content_settings')->load('node.article');
    $this->assertFalse($configuration, 'The language configuration was deleted after bundle was deleted.');
  }

  /**
   * Tests that the configuration is retained when a vocabulary is updated.
   */
  public function testTaxonomyVocabularyUpdate() {
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Country',
      'vid' => 'country',
    ));
    $vocabulary->save();

    $admin_user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->drupalLogin($admin_user);
    $edit = array(
      'default_language[langcode]' => 'current_interface',
      'default_language[language_alterable]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/taxonomy/manage/country', $edit, t('Save'));

    // Check the language default configuration.
    $configuration = ContentLanguageSettings::loadByEntityTypeBundle('taxonomy_term', 'country');
    $uuid = $configuration->uuid();
    $this->assertEqual($configuration->getDefaultLangcode(), 'current_interface', 'The default language configuration has been saved on the Country vocabulary.');
    $this->assertTrue($configuration->isLanguageAlterable(), 'The alterable language configuration has been saved on the Country vocabulary.');
    // Update the vocabulary.
    $edit = array(
      'name' => 'Nation'
    );
    $this->drupalPostForm('admin/structure/taxonomy/manage/country', $edit, t('Save'));
    // Check that we still have the settings for the updated vocabulary.
    $configuration = ContentLanguageSettings::loadByEntityTypeBundle('taxonomy_term', 'country');
    $this->assertEqual($configuration->getDefaultLangcode(), 'current_interface', 'The default language configuration has been kept on the updated Country vocabulary.');
    $this->assertTrue($configuration->isLanguageAlterable(), 'The alterable language configuration has been kept on the updated Country vocabulary.');
    $this->assertEqual($configuration->uuid(), $uuid, 'The language configuration uuid has been kept on the updated Country vocabulary.');
  }

}
