<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;
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
  public static $modules = ['file'];

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
      'aggregator_item' => 10,
      'aggregator_feed' => 1,
      'block' => 25,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 1,
      'comment_type' => 8,
      // Module 'language' comes with 'en', 'und', 'zxx'. Migration adds 'is'.
      'configurable_language' => 4,
      'contact_form' => 3,
      'editor' => 2,
      'field_config' => 53,
      'field_storage_config' => 40,
      'file' => 3,
      'filter_format' => 7,
      'image_style' => 6,
      'language_content_settings' => 2,
      'migration' => 73,
      'node' => 3,
      'node_type' => 6,
      'rdf_mapping' => 7,
      'search_page' => 2,
      'shortcut' => 6,
      'shortcut_set' => 2,
      'action' => 16,
      'menu' => 6,
      'taxonomy_term' => 18,
      'taxonomy_vocabulary' => 4,
      'tour' => 4,
      'user' => 4,
      'user_role' => 3,
      'menu_link_content' => 7,
      'view' => 14,
      'date_format' => 11,
      'entity_form_display' => 18,
      'entity_form_mode' => 1,
      'entity_view_display' => 29,
      'entity_view_mode' => 14,
      'base_field_override' => 9,
    ];
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgrade() {
    parent::testMigrateUpgrade();

    // Ensure migrated users can log in.
    $user = User::load(2);
    $user->passRaw = 'a password';
    $this->drupalLogin($user);
  }

}
