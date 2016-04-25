<?php

namespace Drupal\content_translation\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test enabling content translation module.
 *
 * @group content_translation
 */
class ContentTranslationEnableTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'menu_link_content', 'node'];

  /**
   * Tests that entity schemas are up-to-date after enabling translation.
   */
  public function testEnable() {
    $this->drupalLogin($this->rootUser);
    // Enable modules and make sure the related config entity type definitions
    // are installed.
    $edit = [
      'modules[Multilingual][content_translation][enable]' => TRUE,
      'modules[Multilingual][language][enable]' => TRUE,
    ];
    $this->drupalPostForm('admin/modules', $edit, t('Install'));

    // Status messages are shown.
    $this->assertText(t('This site has only a single language enabled. Add at least one more language in order to translate content.'));
    $this->assertText(t('Enable translation for content types, taxonomy vocabularies, accounts, or any other element you wish to translate.'));

    // No pending updates should be available.
    $this->drupalGet('admin/reports/status');
    $requirement_value = $this->cssSelect("tr.system-status-report__entry th:contains('Entity/field definitions') + td");
    $this->assertEqual(t('Up to date'), trim((string) $requirement_value[0]));

    $this->drupalGet('admin/config/regional/content-language');
    // The node entity type should not be an option because it has no bundles.
    $this->assertNoRaw('entity_types[node]');
    // Enable content translation on entity types that have will have a
    // content_translation_uid.
    $edit = [
      'entity_types[menu_link_content]' => TRUE,
      'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
      'entity_types[entity_test_mul]' => TRUE,
      'settings[entity_test_mul][entity_test_mul][translatable]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // No pending updates should be available.
    $this->drupalGet('admin/reports/status');
    $requirement_value = $this->cssSelect("tr.system-status-report__entry th:contains('Entity/field definitions') + td");
    $this->assertEqual(t('Up to date'), trim((string) $requirement_value[0]));

    // Create a node type and check the content translation settings are now
    // available for nodes.
    $edit = array(
      'name' => 'foo',
      'title_label' => 'title for foo',
      'type' => 'foo',
    );
    $this->drupalPostForm('admin/structure/types/add', $edit, t('Save content type'));
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertRaw('entity_types[node]');
  }

}
