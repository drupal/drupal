<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional\migrate_drupal_ui\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal 6 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 */
#[Group('contact')]
#[Group('#slow')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class Upgrade6Test extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'contact',
    'content_translation',
    'migrate_drupal_ui',
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
    $this->loadFixture($this->getModulePath('contact') . '/tests/fixtures/drupal6.php');
    $this->expectedLoggedErrors = 12;
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
      'action' => 30,
      'base_field_override' => 6,
      'block' => 31,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 1,
      'comment_type' => 3,
      'configurable_language' => 5,
      'contact_form' => 4,
      'contact_message' => 0,
      'date_format' => 12,
      'editor' => 2,
      'entity_form_display' => 7,
      'entity_form_mode' => 1,
      'entity_view_display' => 11,
      'entity_view_mode' => 11,
      'field_config' => 13,
      'field_storage_config' => 10,
      'file' => 1,
      'filter_format' => 7,
      'image_style' => 4,
      'language_content_settings' => 4,
      'menu' => 8,
      'menu_link_content' => 6,
      'node' => 1,
      'node_type' => 2,
      'path_alias' => 0,
      'search_page' => 3,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'taxonomy_term' => 1,
      'taxonomy_vocabulary' => 1,
      'user' => 3,
      'user_role' => 7,
      'view' => 14,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    $counts = $this->getEntityCounts();
    $counts['block_content'] = 2;
    $counts['comment'] = 2;
    $counts['file'] = 2;
    $counts['menu_link_content'] = 7;
    $counts['node'] = 2;
    $counts['taxonomy_term'] = 2;
    $counts['user'] = 4;
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
      // source database.
      'Date API',
      'Date Timezone',
      'Event',
      'ImageAPI',
      'Number',
      'Profile',
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

    $this->assertLogError();
  }

}
