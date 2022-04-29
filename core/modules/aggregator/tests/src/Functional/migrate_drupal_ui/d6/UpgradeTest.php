<?php

namespace Drupal\Tests\aggregator\Functional\migrate_drupal_ui\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;

/**
 * Tests Drupal 6 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group aggregator
 * @group legacy
 */
class UpgradeTest extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aggregator',
    'book',
    'config_translation',
    'content_translation',
    'datetime_range',
    'forum',
    'language',
    'migrate_drupal_ui',
    'statistics',
    'telephone',
    'update',
  ];

  /**
   * The entity storage for node.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    MigrateUpgradeTestBase::setUp();
    $this->loadFixture($this->getModulePath('aggregator') . '/tests/fixtures/drupal6.php');
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
      'block' => 36,
      'block_content' => 2,
      'block_content_type' => 1,
      'comment' => 8,
      // The 'standard' profile provides the 'comment' comment type, and the
      // migration creates 12 comment types, one per node type.
      'comment_type' => 14,
      'contact_form' => 5,
      'contact_message' => 0,
      'configurable_language' => 5,
      'editor' => 2,
      'field_config' => 103,
      'field_storage_config' => 71,
      'file' => 6,
      'filter_format' => 7,
      'image_style' => 6,
      'language_content_settings' => 15,
      'node' => 18,
      // The 'book' module provides the 'book' node type, and the migration
      // creates 12 node types.
      'node_type' => 14,
      'rdf_mapping' => 7,
      'search_page' => 2,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'action' => 33,
      'menu' => 8,
      'path_alias' => 8,
      'taxonomy_term' => 15,
      'taxonomy_vocabulary' => 7,
      'tour' => 6,
      'user' => 7,
      'user_role' => 7,
      'menu_link_content' => 9,
      'view' => 16,
      'date_format' => 12,
      'entity_form_display' => 31,
      'entity_form_mode' => 1,
      'entity_view_display' => 61,
      'entity_view_mode' => 14,
      'base_field_override' => 41,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [
      'Aggregator',
      'Block',
      'Block translation',
      'Book',
      'CCK translation',
      'Comment',
      'Contact',
      'Content',
      'Content translation',
      'Content type translation',
      'Date',
      'Email',
      'FileField',
      'Filter',
      'Forum',
      'ImageCache',
      'ImageField',
      'Internationalization',
      'Locale',
      'Menu',
      'Menu translation',
      'Node',
      'Node Reference',
      'Option Widgets',
      'Path',
      'Profile translation',
      'Search',
      'Statistics',
      'String translation',
      'Synchronize translations',
      'System',
      'Taxonomy',
      'Taxonomy translation',
      'Text',
      'Update status',
      'Upload',
      'User',
      'User Reference',
      // Include modules that do not have an upgrade path and are enabled in the
      // source database'.
      'Date API',
      'Date Timezone',
      'Event',
      'ImageAPI',
      'Number',
      'PHP filter',
      'Profile',
      'Variable admin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [];
  }

  /**
   * Executes an upgrade.
   */
  public function testUpgrade() {
    // Start the upgrade process.
    $this->submitCredentialForm();
    $session = $this->assertSession();

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Test the review form.
    $this->assertReviewForm();

    $this->useTestMailCollector();
    $this->submitForm([], 'Perform upgrade');
    $this->assertUpgrade($this->getEntityCounts());
  }

}
