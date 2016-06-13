<?php

namespace Drupal\migrate_drupal_ui\Tests\d6;

use Drupal\migrate_drupal_ui\Tests\MigrateUpgradeTestBase;
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
      'block' => 30,
      'block_content' => 2,
      'block_content_type' => 1,
      'comment' => 3,
      'comment_type' => 2,
      'contact_form' => 5,
      'editor' => 2,
      'field_config' => 62,
      'field_storage_config' => 43,
      'file' => 7,
      'filter_format' => 7,
      'image_style' => 5,
      'migration' => 105,
      'node' => 9,
      'node_type' => 11,
      'rdf_mapping' => 5,
      'search_page' => 2,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'action' => 22,
      'menu' => 8,
      'taxonomy_term' => 6,
      'taxonomy_vocabulary' => 6,
      'tour' => 1,
      'user' => 7,
      'user_role' => 6,
      'menu_link_content' => 4,
      'view' => 12,
      'date_format' => 11,
      'entity_form_display' => 15,
      'entity_form_mode' => 1,
      'entity_view_display' => 32,
      'entity_view_mode' => 12,
      'base_field_override' => 33,
    ];
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  protected function testMigrateUpgrade() {
    parent::testMigrateUpgrade();

    // Ensure migrated users can log in.
    $user = User::load(2);
    $user->pass_raw = 'john.doe_pass';
    $this->drupalLogin($user);
  }

}
