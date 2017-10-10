<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;
use Drupal\user\Entity\User;

/**
 * Tests Drupal 6 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group migrate_drupal_ui
 */
class MigrateUpgrade6Test extends MigrateUpgradeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal6.php');
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
      'aggregator_item' => 1,
      'aggregator_feed' => 1,
      'block' => 35,
      'block_content' => 2,
      'block_content_type' => 1,
      'comment' => 6,
      // The 'standard' profile provides the 'comment' comment type, and the
      // migration creates 12 comment types, one per node type.
      'comment_type' => 13,
      'contact_form' => 5,
      'configurable_language' => 5,
      'editor' => 2,
      'field_config' => 84,
      'field_storage_config' => 58,
      'file' => 7,
      'filter_format' => 7,
      'image_style' => 5,
      'language_content_settings' => 2,
      'migration' => 105,
      'node' => 17,
      // The 'book' module provides the 'book' node type, and the migration
      // creates 12 node types.
      'node_type' => 13,
      'rdf_mapping' => 7,
      'search_page' => 2,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'action' => 23,
      'menu' => 8,
      'taxonomy_term' => 8,
      'taxonomy_vocabulary' => 7,
      'tour' => 4,
      'user' => 7,
      'user_role' => 6,
      'menu_link_content' => 4,
      'view' => 16,
      'date_format' => 11,
      'entity_form_display' => 29,
      'entity_form_mode' => 1,
      'entity_view_display' => 53,
      'entity_view_mode' => 14,
      'base_field_override' => 38,
    ];
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgrade() {
    parent::testMigrateUpgrade();

    // Ensure migrated users can log in.
    $user = User::load(2);
    $user->passRaw = 'john.doe_pass';
    $this->drupalLogin($user);
  }

}
