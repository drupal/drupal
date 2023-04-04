<?php

namespace Drupal\Tests\forum\Functional\migrate_drupal\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

/**
 * Tests Drupal 6 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group forum
 */
class Upgrade6Test extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'forum',
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
    $this->loadFixture($this->getModulePath('forum') . '/tests/fixtures/drupal6.php');
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
      'block' => 32,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 4,
      'comment_type' => 8,
      'contact_form' => 2,
      'contact_message' => 0,
      'editor' => 2,
      'field_config' => 38,
      'field_storage_config' => 22,
      'file' => 2,
      'filter_format' => 7,
      'image_style' => 6,
      'node' => 2,
      'node_type' => 7,
      'search_page' => 2,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'action' => 27,
      'menu' => 8,
      'path_alias' => 4,
      'taxonomy_term' => 3,
      'taxonomy_vocabulary' => 2,
      'tour' => 2,
      'user' => 3,
      'user_role' => 4,
      'menu_link_content' => 1,
      'view' => 14,
      'date_format' => 12,
      'entity_form_display' => 18,
      'entity_form_mode' => 1,
      'entity_view_display' => 31,
      'entity_view_mode' => 11,
      'base_field_override' => 22,
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
  protected function getAvailablePaths() {
    return [
      'Block',
      'Comment',
      'Content',
      'Date',
      'Date API',
      'Date Timezone',
      'Email',
      'Event',
      'FileField',
      'Filter',
      'Forum',
      'ImageAPI',
      'ImageCache',
      'ImageField',
      'Menu',
      'Node',
      'Path',
      'Search',
      'System',
      'Taxonomy',
      'Upload',
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
  public function testUpgrade() {
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
