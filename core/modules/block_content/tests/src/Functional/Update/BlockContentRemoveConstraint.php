<?php

namespace Drupal\Tests\block_content\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for removing unique constraint on blocks.
 *
 * @group block_content
 */
class BlockContentRemoveConstraint extends UpdatePathTestBase {

  /**
   * Entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityDefinitionUpdateManager = \Drupal::entityDefinitionUpdateManager();
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests the upgrade path for moderation state reindexing.
   */
  public function testRunUpdates() {
    $constraint = 'UniqueField';
    $constraints = $this->getFieldInfoConstraints();
    if (!isset($constraints[$constraint])) {
      $constraints[$constraint] = [];
      $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition('info', 'block_content');
      $field_storage_definition->setConstraints($constraints);
      $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($field_storage_definition);
    }

    $this->assertCount(2, $this->getFieldInfoConstraints());

    $this->runUpdates();

    $this->assertCount(1, $this->getFieldInfoConstraints());
  }

  /**
   * Get constraints for info field.
   *
   * @return array[]
   *   List of constraints.
   */
  protected function getFieldInfoConstraints() {
    $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition('info', 'block_content');
    return $field_storage_definition->getConstraints();
  }

}
