<?php

namespace Drupal\migrate_drupal\Tests;

use Drupal\migrate\Row;

/**
 * Provides common functionality for testing stubbing.
 */
trait StubTestTrait {

  /**
   * Test that creating a stub of the given entity type results in a valid
   * entity.
   *
   * @param string $entity_type_id
   *   The entity type we are stubbing.
   */
  protected function performStubTest($entity_type_id) {
    $entity_id = $this->createStub($entity_type_id);
    $this->assertNotEmpty($entity_id, 'Stub successfully created');
    if ($entity_id) {
      $violations = $this->validateStub($entity_type_id, $entity_id);
      if (!$this->assertIdentical(count($violations), 0, 'Stub is a valid entity')) {
        foreach ($violations as $violation) {
          $this->fail((string) $violation->getMessage());
        }
      }
    }
  }

  /**
   * Create a stub of the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type we are stubbing.
   *
   * @return int
   *   ID of the created entity.
   */
  protected function createStub($entity_type_id) {
    // Create a dummy migration to pass to the destination plugin.
    $definition = [
      'migration_tags' => ['Stub test'],
      'source' => ['plugin' => 'empty'],
      'process' => [],
      'destination' => ['plugin' => 'entity:' . $entity_type_id],
    ];
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $destination_plugin = $migration->getDestinationPlugin(TRUE);
    $stub_row = new Row([], [], TRUE);
    $destination_ids = $destination_plugin->import($stub_row);
    return reset($destination_ids);
  }

  /**
   * Perform validation on a stub entity.
   *
   * @param string $entity_type_id
   *   The entity type we are stubbing.
   * @param string $entity_id
   *   ID of the stubbed entity to validate.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   List of constraint violations identified.
   */
  protected function validateStub($entity_type_id, $entity_id) {
    $controller = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $stub_entity */
    $stub_entity = $controller->load($entity_id);
    return $stub_entity->validate();
  }

}
