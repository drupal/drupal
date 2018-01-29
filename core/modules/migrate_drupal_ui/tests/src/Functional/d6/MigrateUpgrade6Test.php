<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;
use Drupal\user\Entity\User;

/**
 * Tests Drupal 6 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group migrate_drupal_ui
 */
class MigrateUpgrade6Test extends MigrateUpgradeExecuteTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language',
    'content_translation',
    'migrate_drupal_ui',
    'telephone',
    'aggregator',
    'book',
    'forum',
    'statistics',
    'migration_provider_test',
  ];

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
      'aggregator_feed' => 2,
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
      'file' => 8,
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
      'menu_link_content' => 5,
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
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    $counts = $this->getEntityCounts();
    $counts['block_content'] = 3;
    $counts['comment'] = 7;
    $counts['entity_view_display'] = 53;
    $counts['entity_view_mode'] = 14;
    $counts['file'] = 9;
    $counts['menu_link_content'] = 6;
    $counts['node'] = 18;
    $counts['taxonomy_term'] = 9;
    $counts['user'] = 8;
    $counts['view'] = 16;
    return $counts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [
      'aggregator',
      'block',
      'book',
      'comment',
      'contact',
      'content',
      'date',
      'dblog',
      'email',
      'filefield',
      'filter',
      'forum',
      'i18ntaxonomy',
      'imagecache',
      'imagefield',
      'language',
      'link',
      'locale',
      'menu',
      'node',
      'nodereference',
      'optionwidgets',
      'path',
      'profile',
      'search',
      'statistics',
      'system',
      'taxonomy',
      'text',
      'upload',
      'user',
      'userreference',
      // Include modules that do not have an upgrade path and are enabled in the
      // source database, defined in the $noUpgradePath property
      // in MigrateUpgradeForm.
      'date_api',
      'date_timezone',
      'event',
      'i18n',
      'i18nstrings',
      'imageapi',
      'number',
      'php',
      'profile',
      'variable_admin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      'i18nblocks',
      'i18ncck',
      'i18ncontent',
      'i18nmenu',
      // This module is in the missing path list because it is installed on the
      // source site but it is not installed on the destination site.
      'i18nprofile',
    ];
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgradeExecute() {
    parent::testMigrateUpgradeExecute();

    // Ensure migrated users can log in.
    $user = User::load(2);
    $user->passRaw = 'john.doe_pass';
    $this->drupalLogin($user);
  }

}
