<?php

namespace Drupal\Tests\menu_link_content\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for custom menu links.
 *
 * @group menu_link_content
 * @group Update
 */
class MenuLinkContentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.0.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests the bundle key is correctly removed from entity.
   *
   * @see menu_link_content_update_9200()
   */
  public function testBundleKeyDeletion() {
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('menu_link_content');
    $schema = \Drupal::database()->schema();
    $this->assertTrue($entity_type->hasKey('bundle'));
    $this->assertTrue($schema->fieldExists($entity_type->getBaseTable(), 'bundle'));

    $this->runUpdates();

    // Make sure entity doesn't have the 'bundle' key anymore.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('menu_link_content');
    $this->assertFalse($entity_type->hasKey('bundle'));

    // Check there are no leftover in the database schema.
    $this->assertFalse($schema->fieldExists($entity_type->getBaseTable(), 'bundle'));
  }

}
