<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the addition of the revision metadata key.
 *
 * @group workspaces
 * @group legacy
 */
class WorkspaceRevisionMetadataFieldTest extends BrowserTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_revlog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

  /**
   * Tests the addition of the 'workspaces' revision metadata field.
   *
   * @expectedDeprecation The revision_user revision metadata key is not set for entity type: entity_test_mul_revlog_pub See: https://www.drupal.org/node/2831499
   * @expectedDeprecation The revision_created revision metadata key is not set for entity type: entity_test_mul_revlog_pub See: https://www.drupal.org/node/2831499
   * @expectedDeprecation The revision_log_message revision metadata key is not set for entity type: entity_test_mul_revlog_pub See: https://www.drupal.org/node/2831499
   */
  public function testWorkspacesField() {
    $revision_metadata_keys_prev_live = $this->entityTypeManager->getDefinition('entity_test_mul_revlog_pub')
      ->getRevisionMetadataKeys();
    $revision_metadata_keys_prev_installed = $this->entityTypeManager->getActiveDefinition('entity_test_mul_revlog_pub')
      ->getRevisionMetadataKeys();

    $this->container->get('module_installer')->install(['workspaces']);
    $this->entityTypeManager->clearCachedDefinitions();

    $revision_metadata_keys_current_live = $this->entityTypeManager->getDefinition('entity_test_mul_revlog_pub')
      ->getRevisionMetadataKeys();
    $revision_metadata_keys_current_installed = $this->entityTypeManager->getActiveDefinition('entity_test_mul_revlog_pub')
      ->getRevisionMetadataKeys();

    // Ensure that the live revision metadata keys have changed only by the
    // workspace revision metadata key.
    $expected_revision_metadata_keys_live = $revision_metadata_keys_prev_live + ['workspace' => 'workspace'];
    asort($expected_revision_metadata_keys_live);
    asort($revision_metadata_keys_current_live);
    $this->assertEquals($expected_revision_metadata_keys_live, $revision_metadata_keys_current_live);

    // Ensure that the installed revision metadata keys have changed only by the
    // workspace revision metadata key.
    $expected_revision_metadata_keys_installed = $revision_metadata_keys_prev_installed + ['workspace' => 'workspace'];
    asort($expected_revision_metadata_keys_installed);
    asort($revision_metadata_keys_current_installed);
    $this->assertEquals($expected_revision_metadata_keys_installed, $revision_metadata_keys_current_installed);
  }

}
