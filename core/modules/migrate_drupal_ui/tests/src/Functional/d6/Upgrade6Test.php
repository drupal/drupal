<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\node\Entity\Node;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

/**
 * Tests Drupal 6 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group migrate_drupal_ui
 * @group #slow
 */
class Upgrade6Test extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'content_translation',
    'datetime_range',
    'language',
    'migrate_drupal_ui',
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
    parent::setUp();
    // @todo remove in https://www.drupal.org/project/drupal/issues/3267040
    // Delete the existing content made to test the ID Conflict form. Migrations
    // are to be done on a site without content. The test of the ID Conflict
    // form is being moved to its own issue which will remove the deletion
    // of the created nodes.
    // See https://www.drupal.org/project/drupal/issues/3087061.
    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    $this->nodeStorage->delete($this->nodeStorage->loadMultiple());

    $this->loadFixture($this->getModulePath('migrate_drupal') . '/tests/fixtures/drupal6.php');

    $this->expectedLoggedErrors = 39;
    // If saving the logs, then set the admin user.
    if ($this->outputLogs) {
      $this->migratedAdminUserName = 'admin';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath(): string {
    return __DIR__ . '/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts(): array {
    return [
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
      'field_config' => 102,
      'field_storage_config' => 71,
      'file' => 7,
      'filter_format' => 7,
      'image_style' => 6,
      'language_content_settings' => 15,
      'node' => 18,
      // The 'book' module provides the 'book' node type, and the migration
      // creates 12 node types.
      'node_type' => 13,
      'search_page' => 3,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'action' => 30,
      'menu' => 8,
      'path_alias' => 8,
      'taxonomy_term' => 15,
      'taxonomy_vocabulary' => 7,
      'user' => 7,
      'user_role' => 7,
      'menu_link_content' => 10,
      'view' => 14,
      'date_format' => 12,
      'entity_form_display' => 29,
      'entity_form_mode' => 1,
      'entity_view_display' => 55,
      'entity_view_mode' => 12,
      'base_field_override' => 39,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    $counts = $this->getEntityCounts();
    $counts['block_content'] = 3;
    $counts['comment'] = 9;
    $counts['file'] = 8;
    $counts['menu_link_content'] = 11;
    $counts['node'] = 19;
    $counts['taxonomy_term'] = 16;
    $counts['user'] = 8;
    return $counts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths(): array {
    return [
      'Block',
      'Block translation',
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
      'ImageCache',
      'ImageField',
      'Internationalization',
      'Locale',
      'Menu',
      'Menu translation',
      'Node',
      'Node Reference',
      'Node Reference URL Widget',
      'Option Widgets',
      'Path',
      'Profile translation',
      'Search',
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
  protected function getMissingPaths(): array {
    return [
      'Aggregator',
      'Book',
      'Forum',
      'Statistics',
    ];
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testUpgradeAndIncremental(): void {
    // Perform upgrade followed by an incremental upgrade.
    $this->doUpgradeAndIncremental();

    // Ensure a migrated user can log in.
    $this->assertUserLogIn(2, 'john.doe_pass');

    $this->assertFollowUpMigrationResults();
    $this->assertEntityRevisionsCount('node', 26);
    $this->assertEmailsSent();
    $this->assertLogError();
  }

  /**
   * Tests that follow-up migrations have been run successfully.
   *
   * @internal
   */
  protected function assertFollowUpMigrationResults(): void {
    $node = Node::load(10);
    $this->assertSame('12', $node->get('field_reference')->target_id);
    $this->assertSame('12', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('fr');
    $this->assertSame('12', $translation->get('field_reference')->target_id);
    $this->assertSame('12', $translation->get('field_reference_2')->target_id);

    $node = Node::load(12)->getTranslation('en');
    $this->assertSame('10', $node->get('field_reference')->target_id);
    $this->assertSame('10', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('fr');
    $this->assertSame('10', $translation->get('field_reference')->target_id);
    $this->assertSame('10', $translation->get('field_reference_2')->target_id);
  }

}
