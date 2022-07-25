<?php

namespace Drupal\Tests\aggregator\Functional\migrate_drupal_ui\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;

// cspell:ignore Filefield Multiupload Imagefield

/**
 * Tests Drupal 7 upgrade using the migrate UI.
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
    $this->loadFixture($this->getModulePath('aggregator') . '/tests/fixtures/drupal7.php');
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
      'block' => 27,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 4,
      // The 'standard' profile provides the 'comment' comment type, and the
      // migration creates 6 comment types, one per node type.
      'comment_type' => 9,
      // Module 'language' comes with 'en', 'und', 'zxx'. Migration adds 'is'
      // and 'fr'.
      'configurable_language' => 5,
      'contact_form' => 3,
      'contact_message' => 0,
      'editor' => 2,
      'field_config' => 91,
      'field_storage_config' => 70,
      'file' => 3,
      'filter_format' => 7,
      'image_style' => 7,
      'language_content_settings' => 24,
      'node' => 7,
      'node_type' => 8,
      'search_page' => 2,
      'shortcut' => 6,
      'shortcut_set' => 2,
      'action' => 27,
      'menu' => 7,
      'taxonomy_term' => 25,
      'taxonomy_vocabulary' => 8,
      'path_alias' => 8,
      'tour' => 6,
      'user' => 4,
      'user_role' => 4,
      'menu_link_content' => 11,
      'view' => 16,
      'date_format' => 12,
      'entity_form_display' => 24,
      'entity_form_mode' => 1,
      'entity_view_display' => 37,
      'entity_view_mode' => 14,
      'base_field_override' => 4,
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
      'Block languages',
      'Block',
      'Book',
      'Chaos tools',
      'Comment',
      'Contact',
      'Content translation',
      'Database logging',
      'Date',
      'Email',
      'Entity Reference',
      'Entity Translation',
      'Field SQL storage',
      'Field translation',
      'Field',
      'File',
      'Filter',
      'Forum',
      'Image',
      'Internationalization',
      'Locale',
      'Link',
      'List',
      'Menu',
      'Menu translation',
      'Multiupload Filefield Widget',
      'Multiupload Imagefield Widget',
      'Node',
      'Node Reference',
      'Number',
      'Options',
      'Path',
      'Phone',
      'Search',
      'Shortcut',
      'Statistics',
      'String translation',
      'Synchronize translations',
      'System',
      'Taxonomy translation',
      'Taxonomy',
      'Telephone',
      'Text',
      'Title',
      'User',
      'User Reference',
      'Variable translation',
      // Include modules that do not have an upgrade path and are enabled in the
      // source database.
      'Blog',
      'Contextual links',
      'Date API',
      'Entity API',
      'Field UI',
      'Help',
      'PHP filter',
      'Testing',
      'Toolbar',
      'Trigger',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      'Color',
      'RDF',
      'References',
      'Translation sets',
      'Variable realm',
      'Variable store',
      'Variable',
      // These modules are in the missing path list because they are installed
      // on the source site but they are not installed on the destination site.
      'Syslog',
      'Tracker',
      'Update manager',
    ];
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
