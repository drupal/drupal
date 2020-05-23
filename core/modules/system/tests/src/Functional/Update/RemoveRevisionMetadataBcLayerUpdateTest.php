<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for removing the revision metadata BC layer.
 *
 * @see https://www.drupal.org/node/3099789
 *
 * @group Update
 */
class RemoveRevisionMetadataBcLayerUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * @see system_post_update_entity_revision_metadata_bc_cleanup()
   */
  public function testRevisionMetadataBcLayerRemoval() {
    $entity_type = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledDefinition('node');
    $this->assertArrayHasKey("\x00*\x00requiredRevisionMetadataKeys", (array) $entity_type);

    $this->runUpdates();

    $entity_type = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledDefinition('node');
    $this->assertArrayNotHasKey("\x00*\x00requiredRevisionMetadataKeys", (array) $entity_type);
  }

}
