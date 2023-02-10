<?php

namespace Drupal\Tests\forum\Functional\migrate_drupal\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

// cspell:ignore Filefield Multiupload Imagefield

/**
 * Tests Drupal 7 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group forum
 */
class Upgrade7Test extends MigrateUpgradeExecuteTestBase {

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

    // @todo remove in https://www.drupal.org/project/drupal/issues/3267040
    // Delete the existing content made to test the ID Conflict form. Migrations
    // are to be done on a site without content. The test of the ID Conflict
    // form is being moved to its own issue which will remove the deletion
    // of the created nodes.
    // See https://www.drupal.org/project/drupal/issues/3087061.
    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    $this->nodeStorage->delete($this->nodeStorage->loadMultiple());

    $this->loadFixture($this->getModulePath('forum') . '/tests/fixtures/drupal7.php');
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
      'block' => 25,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 0,
      'comment_type' => 5,
      'contact_form' => 2,
      'contact_message' => 0,
      'editor' => 2,
      'field_config' => 20,
      'field_storage_config' => 14,
      'file' => 2,
      'filter_format' => 7,
      'image_style' => 7,
      'node' => 2,
      'node_type' => 4,
      'search_page' => 2,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'action' => 27,
      'menu' => 5,
      'taxonomy_term' => 6,
      'taxonomy_vocabulary' => 2,
      'path_alias' => 1,
      'tour' => 2,
      'user' => 4,
      'user_role' => 4,
      'menu_link_content' => 3,
      'view' => 14,
      'date_format' => 12,
      'entity_form_display' => 12,
      'entity_form_mode' => 1,
      'entity_view_display' => 18,
      'entity_view_mode' => 11,
      'base_field_override' => 3,
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
      'Block',
      'Comment',
      'Content translation',
      'Date',
      'Field SQL storage',
      'Field',
      'File',
      'Filter',
      'Forum',
      'Image',
      'Menu',
      'Node',
      'Options',
      'Path',
      'Search',
      'System',
      'Taxonomy',
      'Text',
      'User',
      'Contextual links',
      'Date API',
      'Field UI',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      'Locale',
    ];
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
