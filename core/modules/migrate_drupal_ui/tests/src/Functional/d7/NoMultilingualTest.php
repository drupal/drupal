<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

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
    'file',
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
      'aggregator_item' => 11,
      'aggregator_feed' => 1,
      'block' => 25,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 2,
      // The 'standard' profile provides the 'comment' comment type, and the
      // migration creates 6 comment types, one per node type.
      'comment_type' => 7,
      // Module 'language' comes with 'en', 'und', 'zxx'. Migration adds 'is'.
      'configurable_language' => 4,
      'contact_form' => 3,
      'editor' => 2,
      'field_config' => 68,
      'field_storage_config' => 50,
      'file' => 3,
      'filter_format' => 7,
      'image_style' => 6,
      'language_content_settings' => 2,
      'migration' => 73,
      'node' => 5,
      'node_type' => 6,
      'rdf_mapping' => 8,
      'search_page' => 2,
      'shortcut' => 6,
      'shortcut_set' => 2,
      'action' => 17,
      'menu' => 6,
      'taxonomy_term' => 18,
      'taxonomy_vocabulary' => 4,
      'tour' => 4,
      'user' => 4,
      'user_role' => 3,
      'menu_link_content' => 10,
      'view' => 16,
      'date_format' => 11,
      'entity_form_display' => 17,
      'entity_form_mode' => 1,
      'entity_view_display' => 28,
      'entity_view_mode' => 14,
      'base_field_override' => 9,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    $counts = $this->getEntityCounts();
    $counts['block_content'] = 2;
    $counts['comment'] = 3;
    $counts['file'] = 4;
    $counts['menu_link_content'] = 11;
    $counts['node'] = 6;
    $counts['taxonomy_term'] = 19;
    $counts['user'] = 5;
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
      'color',
      'comment',
      'contact',
      'date',
      'dblog',
      'email',
      'entityreference',
      'field',
      'field_sql_storage',
      'file',
      'filter',
      'forum',
      'image',
      'language',
      'link',
      'list',
      'locale',
      'menu',
      'node',
      'number',
      'options',
      'path',
      'phone',
      'rdf',
      'search',
      'shortcut',
      'statistics',
      'system',
      'taxonomy',
      'text',
      'user',
      // Include modules that do not have an upgrade path and are enabled in the
      // source database.
      'blog',
      'contextual',
      'date_api',
      'entity',
      'field_ui',
      'help',
      'php',
      'simpletest',
      'toolbar',
      'translation',
      'trigger',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getIncompletePaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      // These modules are in the missing path list because they are installed
      // on the source site but they are not installed on the destination site.
      'syslog',
      'tracker',
      'update',
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
    $session->pageTextContains("Install migrate_drupal_multilingual to run migration 'd7_system_maintenance_translation'.");
  }

}
