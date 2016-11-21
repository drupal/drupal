<?php

namespace Drupal\migrate_drupal_ui\Tests\d7;

use Drupal\migrate_drupal_ui\Tests\MigrateUpgradeTestBase;
use Drupal\user\Entity\User;

/**
 * Tests Drupal 7 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group migrate_drupal_ui
 */
class MigrateUpgrade7Test extends MigrateUpgradeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal7.php');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return __DIR__ . '/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    return [
      'block' => 25,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 1,
      'comment_type' => 7,
      // Module 'language' comes with 'en', 'und', 'zxx'. Migration adds 'is'.
      'configurable_language' => 4,
      'contact_form' => 3,
      'editor' => 2,
      'field_config' => 48,
      'field_storage_config' => 36,
      'file' => 1,
      'filter_format' => 7,
      'image_style' => 6,
      'language_content_settings' => 2,
      'migration' => 73,
      'node' => 3,
      'node_type' => 6,
      'rdf_mapping' => 5,
      'search_page' => 2,
      'shortcut' => 6,
      'shortcut_set' => 2,
      'action' => 16,
      'menu' => 6,
      'taxonomy_term' => 18,
      'taxonomy_vocabulary' => 3,
      'tour' => 4,
      'user' => 4,
      'user_role' => 3,
      'menu_link_content' => 7,
      'view' => 12,
      'date_format' => 11,
      'entity_form_display' => 16,
      'entity_form_mode' => 1,
      'entity_view_display' => 24,
      'entity_view_mode' => 11,
      'base_field_override' => 7,
    ];
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  protected function testMigrateUpgrade() {
    parent::testMigrateUpgrade();

    // Ensure migrated users can log in.
    $user = User::load(2);
    $user->pass_raw = 'a password';
    $this->drupalLogin($user);
  }

}
