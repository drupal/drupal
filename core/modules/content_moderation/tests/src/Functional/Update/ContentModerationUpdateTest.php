<?php

namespace Drupal\Tests\content_moderation\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that content moderation settings are updated during database updates.
 *
 * @group content_moderation
 * @group legacy
 */
class ContentModerationUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.4.0-content_moderation_installed.php',
    ];
  }

  /**
   * Tests that the content moderation state entity has an 'owner' entity key.
   *
   * @see content_moderation_update_8700()
   */
  public function testOwnerEntityKey() {
    // Check that the 'owner' entity key does not exist prior to the update.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('content_moderation_state');
    $this->assertFalse($entity_type->getKey('owner'));

    // Run updates.
    $this->runUpdates();

    // Check that the entity key exists and it has the correct value.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('content_moderation_state');
    $this->assertEquals('uid', $entity_type->getKey('owner'));
  }

}
