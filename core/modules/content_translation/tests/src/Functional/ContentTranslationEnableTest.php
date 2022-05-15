<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test enabling content translation module.
 *
 * @group content_translation
 */
class ContentTranslationEnableTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'menu_link_content', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that entity schemas are up-to-date after enabling translation.
   */
  public function testEnable() {
    $this->drupalLogin($this->rootUser);
    // Enable modules and make sure the related config entity type definitions
    // are installed.
    $edit = [
      'modules[content_translation][enable]' => TRUE,
      'modules[language][enable]' => TRUE,
    ];
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    // Status messages are shown.
    $this->assertSession()->statusMessageContains('This site has only a single language enabled. Add at least one more language in order to translate content.', 'warning');
    $this->assertSession()->statusMessageContains('Enable translation for content types, taxonomy vocabularies, accounts, or any other element you wish to translate.', 'warning');

    // No pending updates should be available.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->elementTextEquals('css', "details.system-status-report__entry summary:contains('Entity/field definitions') + div", 'Up to date');

    $this->drupalGet('admin/config/regional/content-language');
    // The node entity type should not be an option because it has no bundles.
    $this->assertSession()->responseNotContains('entity_types[node]');
    // Enable content translation on entity types that have will have a
    // content_translation_uid.
    $edit = [
      'entity_types[menu_link_content]' => TRUE,
      'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
      'entity_types[entity_test_mul]' => TRUE,
      'settings[entity_test_mul][entity_test_mul][translatable]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    // No pending updates should be available.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->elementTextEquals('css', "details.system-status-report__entry summary:contains('Entity/field definitions') + div", 'Up to date');

    // Create a node type and check the content translation settings are now
    // available for nodes.
    $edit = [
      'name' => 'foo',
      'title_label' => 'title for foo',
      'type' => 'foo',
    ];
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm($edit, 'Save content type');
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->responseContains('entity_types[node]');
  }

}
