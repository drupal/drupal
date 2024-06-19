<?php

declare(strict_types=1);

namespace Drupal\Tests\statistics\Functional\migrate_drupal\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

/**
 * Tests Drupal 6 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group statistics
 * @group legacy
 */
class UpgradeTest extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'content_translation',
    'language',
    'migrate_drupal_ui',
    'statistics',
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
    $this->loadFixture($this->getModulePath('statistics') . '/tests/fixtures/drupal6.php');
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
      'action' => 24,
      'base_field_override' => 18,
      'block' => 33,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 2,
      'comment_type' => 7,
      'configurable_language' => 5,
      'contact_form' => 2,
      'contact_message' => 0,
      'date_format' => 12,
      'editor' => 2,
      'entity_form_display' => 16,
      'entity_form_mode' => 1,
      'entity_view_display' => 25,
      'entity_view_mode' => 10,
      'field_config' => 25,
      'field_storage_config' => 14,
      'file' => 1,
      'filter_format' => 7,
      'image_style' => 4,
      'language_content_settings' => 9,
      'menu' => 8,
      'menu_link_content' => 1,
      'node' => 11,
      'node_type' => 7,
      'path_alias' => 0,
      'search_page' => 3,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'taxonomy_term' => 1,
      'taxonomy_vocabulary' => 1,
      'user' => 3,
      'user_role' => 4,
      'view' => 14,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [
      'Block',
      'Content translation',
      'Content',
      'Comment',
      'Filter',
      'Internationalization',
      'Locale',
      'Menu',
      'Node',
      'Path',
      'Statistics',
      'System',
      'User',
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
   * Executes all steps of migrations upgrade.
   */
  public function testUpgrade(): void {
    // Start the upgrade process.
    $this->submitCredentialForm();
    $session = $this->assertSession();

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Test the review form.
    $this->assertReviewForm();

    $this->submitForm([], 'Perform upgrade');
    $this->assertUpgrade($this->getEntityCounts());
  }

}
