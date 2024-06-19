<?php

declare(strict_types=1);

namespace Drupal\Tests\statistics\Functional\migrate_drupal\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

/**
 * Tests Drupal 7 upgrade using the migrate UI.
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
    'datetime_range',
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

    // @todo remove when https://www.drupal.org/project/drupal/issues/3266491 is
    // fixed.
    // Delete the existing content made to test the ID Conflict form. Migrations
    // are to be done on a site without content. The test of the ID Conflict
    // form is being moved to its own issue which will remove the deletion
    // of the created nodes.
    // See https://www.drupal.org/project/drupal/issues/3087061.
    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    $this->nodeStorage->delete($this->nodeStorage->loadMultiple());

    $this->loadFixture($this->getModulePath('statistics') . '/tests/fixtures/drupal7.php');
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
      'base_field_override' => 2,
      'block' => 26,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 4,
      'comment_type' => 9,
      'configurable_language' => 5,
      'contact_form' => 2,
      'contact_message' => 0,
      'date_format' => 12,
      'editor' => 2,
      'entity_form_display' => 19,
      'entity_form_mode' => 1,
      'entity_view_display' => 28,
      'entity_view_mode' => 11,
      'field_config' => 33,
      'field_storage_config' => 19,
      'file' => 1,
      'filter_format' => 7,
      'image_style' => 7,
      'language_content_settings' => 16,
      'menu' => 5,
      'menu_link_content' => 2,
      'node' => 7,
      'node_type' => 8,
      'path_alias' => 0,
      'search_page' => 3,
      'shortcut' => 2,
      'shortcut_set' => 1,
      'taxonomy_term' => 15,
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
      'Contact',
      'Content translation',
      'Entity Translation',
      'Field',
      'Field SQL storage',
      'Field UI',
      'File',
      'Filter',
      'Image',
      'Internationalization',
      'List',
      'Locale',
      'Menu',
      'Node',
      'Number',
      'Options',
      'Path',
      'Statistics',
      'System',
      'Taxonomy',
      'Text',
      'User',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      'Forum',
      'Variable',
    ];
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
