<?php

namespace Drupal\Tests\rdf\Functional\Migrate;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;

/**
 * Tests Drupal 7 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group rdf
 * @group legacy
 */
class Upgrade7Test extends MigrateUpgradeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf',
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
    $this->loadFixture($this->getModulePath('rdf') . '/tests/fixtures/drupal7.php');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return __DIR__;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    return [
      'action' => 21,
      'base_field_override' => 2,
      'block' => 31,
      'block_content' => 0,
      'block_content_type' => 1,
      'comment' => 0,
      'comment_type' => 5,
      'contact_form' => 2,
      'contact_message' => 0,
      'date_format' => 12,
      'editor' => 2,
      'entity_form_display' => 11,
      'entity_form_mode' => 1,
      'entity_view_display' => 17,
      'entity_view_mode' => 10,
      'field_config' => 19,
      'field_storage_config' => 12,
      'file' => 0,
      'filter_format' => 5,
      'image_style' => 4,
      'menu' => 5,
      'menu_link_content' => 1,
      'node' => 0,
      'node_type' => 4,
      'path_alias' => 0,
      'rdf_mapping' => 8,
      'search_page' => 2,
      'shortcut' => 4,
      'shortcut_set' => 1,
      'taxonomy_term' => 1,
      'taxonomy_vocabulary' => 2,
      'tour' => 2,
      'user' => 2,
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
      'Blog',
      'Comment',
      'Contextual links',
      'Dashboard',
      'Database logging',
      'Field',
      'Field SQL storage',
      'Field UI',
      'File',
      'Filter',
      'Help',
      'Image',
      'List',
      'Menu',
      'Node',
      'Number',
      'Options',
      'Overlay',
      'Path',
      'RDF',
      'Search',
      'Shortcut',
      'System',
      'Taxonomy',
      'Text',
      'Toolbar',
      'User',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      'Color',
      'Forum',
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
