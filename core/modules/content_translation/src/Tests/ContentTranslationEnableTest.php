<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\ContentTranslationEnableTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test enabling content translation after other modules.
 *
 * @group content_translation
 */
class ContentTranslationEnableTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'menu_link_content'];

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

    // No pending updates should be available.
    $this->drupalGet('admin/reports/status');
    $requirement_value = $this->cssSelect("tr.system-status-report__entry th:contains('Entity/field definitions') + td");
    $this->assertEqual(t('Up to date'), trim((string) $requirement_value[0]));

    // Enable content translation on entity types that have will have a
    // content_translation_uid.
    $edit = [
      'entity_types[menu_link_content]' => TRUE,
      'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
      'entity_types[entity_test_mul]' => TRUE,
      'settings[entity_test_mul][entity_test_mul][translatable]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));

    // No pending updates should be available.
    $this->drupalGet('admin/reports/status');
    $requirement_value = $this->cssSelect("tr.system-status-report__entry th:contains('Entity/field definitions') + td");
    $this->assertEqual(t('Up to date'), trim((string) $requirement_value[0]));
  }

}
