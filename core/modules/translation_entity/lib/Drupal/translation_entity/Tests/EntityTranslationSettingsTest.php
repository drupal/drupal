<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\EntityTranslationSettingsTest.
 */

namespace Drupal\translation_entity\Tests;

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

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  function setUp() {
    parent::setUp();

    // Setup two content types to test instances shared among different bundles.
    $this->drupalCreateContentType(array('type' => 'article'));
    $this->drupalCreateContentType(array('type' => 'page'));

    $admin_user = $this->drupalCreateUser(array('administer entity translation'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the settings UI works as expected.
   */
  function testSettingsUI() {
    // Test that by marking only an entity type and no bundle as translatable a
    // form error is raised and the settings are not saved.
    $edit = array('entity_types[comment]' => TRUE);
    $this->assertSettings('comment', NULL, FALSE, $edit);
    $this->assertTrue($this->xpath('//div[@id="messages"]//div[contains(@class, "error")]'), 'Enabling translation only for entity types generates a form error.');

    // Test that by marking only a bundle and not the related entity type as
    // translatable the settings are ignored.
    $edit = array('settings[comment][comment_node_article][translatable]' => TRUE);
    $this->assertSettings('comment', NULL, FALSE, $edit);

    // Test that by marking only a field as translatable and not the related
    // entity type and bundle the settings are ignored.
    $edit = array('settings[comment][comment_node_article][fields][comment_body]' => TRUE);
    $this->assertSettings('comment', NULL, FALSE, $edit);

    // Test that by marking entity type and bundle as translatable the settings
    // are stored.
    $edit = array(
      'entity_types[comment]' => TRUE,
      'settings[comment][comment_node_article][translatable]' => TRUE,
    );
    $this->assertSettings('comment', 'comment_node_article', TRUE, $edit);

    // Test that a field shared among different bundles can be enabled without
    // needing to make all the related bundles translatable.
    $edit = array(
      'settings[comment][comment_node_article][settings][language][langcode]' => 'current_interface',
      'settings[comment][comment_node_article][settings][language][language_hidden]' => FALSE,
      'settings[comment][comment_node_article][fields][comment_body]' => TRUE,
    );
    $this->assertSettings('comment', 'comment_node_article', TRUE, $edit);
    $field = field_info_field('comment_body');
    $this->assertTrue($field['translatable'], 'Comment body is translatable.');

    // Test that language settings are correctly stored.
    $language_configuration = language_get_default_configuration('comment', 'comment_node_article');
    $this->assertEqual($language_configuration['langcode'], 'current_interface', 'The default language for article comments is set to the currrent interface language.');
    $this->assertFalse($language_configuration['language_hidden'], 'The language selector for article comments is shown.');
  }

  /**
   * Asserts that translatability has the expected value for the given bundle.
   */
  protected function assertSettings($entity_type, $bundle, $enabled, $edit) {
    $this->drupalPost('admin/config/regional/translation_entity', $edit, t('Save settings'));
    $args = array('@entity_type' => $entity_type, '@bundle' => $bundle, '@enabled' => $enabled ? 'enabled' : 'disabled');
    $message = format_string('Translation for entity @entity_type (@bundle) is @enabled.', $args);
    drupal_static_reset();
    return $this->assertEqual(translation_entity_enabled($entity_type, $bundle), $enabled, $message);
  }

}
