<?php

namespace Drupal\migrate_drupal_ui\Tests\d7;

use Drupal\migrate_drupal_ui\Tests\MigrateUpgradeTestBase;

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
      'contact_form' => 3,
      'editor' => 2,
      'field_config' => 41,
      'field_storage_config' => 31,
      'file' => 1,
      'filter_format' => 7,
      'image_style' => 6,
      'migration' => 59,
      'node' => 2,
      'node_type' => 6,
      'rdf_mapping' => 5,
      'search_page' => 2,
      'shortcut' => 6,
      'shortcut_set' => 2,
      'action' => 18,
      'menu' => 10,
      'taxonomy_term' => 18,
      'taxonomy_vocabulary' => 3,
      'tour' => 1,
      'user' => 3,
      'user_role' => 4,
      'menu_link_content' => 9,
      'view' => 12,
      'date_format' => 11,
      'entity_form_display' => 15,
      'entity_form_mode' => 1,
      'entity_view_display' => 22,
      'entity_view_mode' => 10,
      'base_field_override' => 7,
    ];
  }

}
