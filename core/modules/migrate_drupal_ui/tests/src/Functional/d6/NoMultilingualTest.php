<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

/**
 * Tests Drupal 6 upgrade without translations.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group migrate_drupal_ui
 */
class NoMultilingualTest extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
    'config_translation',
    'migrate_drupal_ui',
    'telephone',
    'aggregator',
    'book',
    'forum',
    'statistics',
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
      'field_config' => 89,
      'field_storage_config' => 63,
      'file' => 8,
      'filter_format' => 7,
      'image_style' => 5,
      'language_content_settings' => 3,
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
      'menu_link_content' => 8,
      'view' => 16,
      'date_format' => 11,
      'entity_form_display' => 29,
      'entity_form_mode' => 1,
      'entity_view_display' => 55,
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
    $counts['file'] = 9;
    $counts['menu_link_content'] = 9;
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
      'imagecache',
      'imagefield',
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
      // source database.
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
      'i18nprofile',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testMigrateUpgradeExecute() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains('Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal 8.');

    $button = $session->buttonExists('Continue');
    $button->click();
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');

    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $edit = [
      $driver => $connection_options,
      'version' => $version,
    ];
    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    $edits = $this->translatePostValues($edit);
    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    $session->pageTextContains("Install migrate_drupal_multilingual to run migration 'd6_system_maintenance_translation'.");
  }

}
