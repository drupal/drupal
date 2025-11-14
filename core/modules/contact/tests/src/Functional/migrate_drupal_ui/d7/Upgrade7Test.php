<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional\migrate_drupal_ui\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal 7 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 */
#[Group('contact')]
#[Group('#slow')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class Upgrade7Test extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'contact',
    'content_translation',
    'migrate_drupal_ui',
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

    $this->loadFixture($this->getModulePath('contact') . '/tests/fixtures/drupal7.php');

    $this->expectedLoggedErrors = 18;
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
      'action' => 24,
      'base_field_override' => 2,
      'block' => 25,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 1,
      'comment_type' => 6,
      'configurable_language' => 5,
      'contact_form' => 2,
      'contact_message' => 0,
      'date_format' => 12,
      'editor' => 2,
      'entity_form_display' => 13,
      'entity_form_mode' => 1,
      'entity_view_display' => 20,
      'entity_view_mode' => 11,
      'field_config' => 31,
      'field_storage_config' => 22,
      'file' => 1,
      'filter_format' => 7,
      'image_style' => 7,
      'language_content_settings' => 14,
      'menu' => 5,
      'menu_link_content' => 2,
      'node' => 1,
      'node_type' => 5,
      'path_alias' => 1,
      'search_page' => 3,
      'shortcut' => 6,
      'shortcut_set' => 2,
      'taxonomy_term' => 2,
      'taxonomy_vocabulary' => 2,
      'user' => 3,
      'user_role' => 4,
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
    $counts['menu_link_content'] = 3;
    $counts['node'] = 2;
    $counts['taxonomy_term'] = 3;
    $counts['user'] = 4;
    return $counts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths(): array {
    return [
      'Block languages',
      'Block',
      'Chaos tools',
      'Comment',
      'Contact',
      'Content translation',
      'Contextual links',
      'Date API',
      'Date',
      'Entity API',
      'Entity Translation',
      'Field SQL storage',
      'Field UI',
      'Field translation',
      'Field',
      'File',
      'Filter',
      'Help',
      'Image',
      'Internationalization',
      'Link',
      'List',
      'Locale',
      'Menu translation',
      'Menu',
      'Node',
      'Options',
      'Path',
      'Search',
      'Shortcut',
      'String translation',
      'Synchronize translations',
      'System',
      'Taxonomy translation',
      'Taxonomy',
      'Text',
      'Toolbar',
      'User',
      'Variable translation',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths(): array {
    return [
      'Syslog',
      'Translation sets',
      'Update manager',
      'Variable realm',
      'Variable store',
      'Variable',
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
