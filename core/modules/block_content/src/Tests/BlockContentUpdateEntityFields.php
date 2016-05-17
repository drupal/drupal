<?php

namespace Drupal\block_content\Tests;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests adding revision_user and revision_created fields.
 *
 * @group Update
 */
class BlockContentUpdateEntityFields extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that email token in status_blocked of user.mail is updated.
   */
  public function testAddingFields() {
    $this->runUpdates();

    $post_revision_created = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('revision_created', 'block_content');
    $post_revision_user = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('revision_user', 'block_content');
    $this->assertTrue($post_revision_created instanceof BaseFieldDefinition, "Revision created field found");
    $this->assertTrue($post_revision_user instanceof BaseFieldDefinition, "Revision user field found");

    $this->assertEqual('created', $post_revision_created->getType(), "Field is type created");
    $this->assertEqual('entity_reference', $post_revision_user->getType(), "Field is type entity_reference");
  }

}
