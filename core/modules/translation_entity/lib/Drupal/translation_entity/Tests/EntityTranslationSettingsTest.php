<?php

/**
 * @file
 * Contains Drupal\translation_entity\Tests\EntityTranslationSettingsTest.
 */

namespace Drupal\translation_entity\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Entity Test Translation UI.
 */
class EntityTranslationSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'translation_entity', 'comment');

  public static function getInfo() {
    return array(
      'name' => 'Entity Translation settings',
      'description' => 'Tests the entity translation settings UI.',
      'group' => 'Entity Translation UI',
    );
  }

  function setUp() {
    parent::setUp();

    // Set up two content types to test field instances shared between different
    // bundles.
    $this->drupalCreateContentType(array('type' => 'article'));
    $this->drupalCreateContentType(array('type' => 'page'));

    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer entity translation'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the settings UI works as expected.
   */
  function testSettingsUI() {
    // Test that the translation settings are ignored if the bundle is marked
    // translatable but the entity type is not.
    $edit = array('settings[comment][comment_node_article][translatable]' => TRUE);
    $this->assertSettings('comment', NULL, FALSE, $edit);

    // Test that the translation settings are ignored if only a field is marked
    // as translatable and not the related entity type and bundle.
    $edit = array('settings[comment][comment_node_article][fields][comment_body]' => TRUE);
    $this->assertSettings('comment', NULL, FALSE, $edit);

    // Test that the translation settings are not stored if an entity type and
    // bundle are marked as translatable but no field is.
    $edit = array(
      'entity_types[comment]' => TRUE,
      'settings[comment][comment_node_article][translatable]' => TRUE,
    );
    $this->assertSettings('comment', 'comment_node_article', FALSE, $edit);
    $xpath_err = '//div[@id="messages"]//div[contains(@class, "error")]';
    $this->assertTrue($this->xpath($xpath_err), 'Enabling translation only for entity bundles generates a form error.');

    // Test that the translation settings are not stored if a non-configurable
    // language is set as default and the language selector is hidden.
    $edit = array(
      'entity_types[comment]' => TRUE,
      'settings[comment][comment_node_article][settings][language][langcode]' => Language::LANGCODE_NOT_SPECIFIED,
      'settings[comment][comment_node_article][settings][language][language_show]' => FALSE,
      'settings[comment][comment_node_article][translatable]' => TRUE,
      'settings[comment][comment_node_article][fields][comment_body]' => TRUE,
    );
    $this->assertSettings('comment', 'comment_node_article', FALSE, $edit);
    $this->assertTrue($this->xpath($xpath_err), 'Enabling translation with a fixed non-configurable language generates a form error.');

    // Test that a field shared among different bundles can be enabled without
    // needing to make all the related bundles translatable.
    $edit = array(
      'entity_types[comment]' => TRUE,
      'settings[comment][comment_node_article][settings][language][langcode]' => 'current_interface',
      'settings[comment][comment_node_article][settings][language][language_show]' => TRUE,
      'settings[comment][comment_node_article][translatable]' => TRUE,
      'settings[comment][comment_node_article][fields][comment_body]' => TRUE,
    );
    $this->assertSettings('comment', 'comment_node_article', TRUE, $edit);
    $field = field_info_field('comment_body');
    $this->assertTrue($field['translatable'], 'Comment body is translatable.');

    // Test that language settings are correctly stored.
    $language_configuration = language_get_default_configuration('comment', 'comment_node_article');
    $this->assertEqual($language_configuration['langcode'], 'current_interface', 'The default language for article comments is set to the current interface language.');
    $this->assertTrue($language_configuration['language_show'], 'The language selector for article comments is shown.');
  }

  /**
   * Asserts that translatability has the expected value for the given bundle.
   *
   * @param string $entity_type
   *   The entity type for which to check translatibility.
   * @param string $bundle
   *   The bundle for which to check translatibility.
   * @param boolean $enabled
   *   TRUE if translatibility should be enabled, FALSE otherwise.
   * @param array $edit
   *   An array of values to submit to the entity translation settings page.
   *
   * @return boolean
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertSettings($entity_type, $bundle, $enabled, $edit) {
    $this->drupalPost('admin/config/regional/content-language', $edit, t('Save'));
    $args = array('@entity_type' => $entity_type, '@bundle' => $bundle, '@enabled' => $enabled ? 'enabled' : 'disabled');
    $message = format_string('Translation for entity @entity_type (@bundle) is @enabled.', $args);
    entity_info_cache_clear();
    return $this->assertEqual(translation_entity_enabled($entity_type, $bundle), $enabled, $message);
  }

}
