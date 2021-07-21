<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\node\Entity\Node;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

// cspell:ignore Multiupload Imagefield

/**
 * Tests Drupal 7 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group migrate_drupal_ui
 */
class Upgrade7Test extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aggregator',
    'book',
    'config_translation',
    'content_translation',
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
    parent::setUp();

    // Delete the existing content made to test the ID Conflict form. Migrations
    // are to be done on a site without content. The test of the ID Conflict
    // form is being moved to its own issue which will remove the deletion
    // of the created nodes.
    // See https://www.drupal.org/project/drupal/issues/3087061.
    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    $this->nodeStorage->delete($this->nodeStorage->loadMultiple());

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
      'field_config' => 90,
      'field_storage_config' => 69,
      'file' => 3,
      'filter_format' => 7,
      'image_style' => 7,
      'language_content_settings' => 22,
      'node' => 7,
      'node_type' => 8,
      'rdf_mapping' => 8,
      'search_page' => 2,
      'shortcut' => 6,
      'shortcut_set' => 2,
      'action' => 19,
      'menu' => 7,
      'taxonomy_term' => 25,
      'taxonomy_vocabulary' => 8,
      'path_alias' => 8,
      'tour' => 6,
      'user' => 4,
      'user_role' => 3,
      'menu_link_content' => 12,
      'view' => 16,
      'date_format' => 11,
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
    $counts = $this->getEntityCounts();
    $counts['block_content'] = 2;
    $counts['comment'] = 5;
    $counts['file'] = 4;
    $counts['menu_link_content'] = 13;
    $counts['node'] = 8;
    $counts['taxonomy_term'] = 26;
    $counts['user'] = 5;
    return $counts;
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
      'Color',
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
      'RDF',
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
   * Executes all steps of migrations upgrade.
   */
  public function testUpgradeAndIncremental() {
    // Perform upgrade followed by an incremental upgrade.
    $this->doUpgradeAndIncremental();

    // Ensure a migrated user can log in.
    $this->assertUserLogIn(2, 'a password');

    $this->assertFollowUpMigrationResults();
  }

  /**
   * Tests that follow-up migrations have been run successfully.
   */
  protected function assertFollowUpMigrationResults() {
    $node = Node::load(2);
    $this->assertSame('4', $node->get('field_reference')->target_id);
    $this->assertSame('4', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('is');
    $this->assertSame('4', $translation->get('field_reference')->target_id);
    $this->assertSame('4', $translation->get('field_reference_2')->target_id);

    $node = Node::load(4);
    $this->assertSame('2', $node->get('field_reference')->target_id);
    $this->assertSame('2', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('en');
    $this->assertSame('2', $translation->get('field_reference')->target_id);
    $this->assertSame('2', $translation->get('field_reference_2')->target_id);

  }

}
