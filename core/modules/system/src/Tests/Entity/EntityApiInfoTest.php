<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityApiInfoTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;

/**
 * Makes sure entity info is accurately cached.
 *
 * @group Entity
 */
class EntityApiInfoTest extends WebTestBase  {

  /**
   * Ensures entity info cache is updated after changes.
   */
  function testEntityInfoChanges() {
    \Drupal::moduleHandler()->install(array('entity_cache_test'));
    $entity_types = \Drupal::entityManager()->getDefinitions();
    $this->assertTrue(isset($entity_types['entity_cache_test']), 'Test entity type found.');

    // Change the label of the test entity type and make sure changes appear
    // after flushing caches.
    \Drupal::state()->set('entity_cache_test.label', 'New label.');
    $entity_type = \Drupal::entityManager()->getDefinition('entity_cache_test');
    $this->assertEqual($entity_type->getLabel(), 'Entity Cache Test', 'Original label appears in cached entity info.');
    $this->resetAll();
    $entity_type = \Drupal::entityManager()->getDefinition('entity_cache_test');
    $this->assertEqual($entity_type->getLabel(), 'New label.', 'New label appears in entity info.');

    // Uninstall the providing module and make sure the entity type is gone.
    $this->container->get('module_handler')->uninstall(array('entity_cache_test', 'entity_cache_test_dependency'));
    $entity_types = \Drupal::entityManager()->getDefinitions();
    $this->assertFalse(isset($entity_types['entity_cache_test']), 'Entity type of the providing module is gone.');
  }

  /**
   * Tests entity info cache after enabling a module with a dependency on an entity providing module.
   *
   * @see entity_cache_test_modules_enabled()
   */
  function testEntityInfoCacheModulesEnabled() {
    \Drupal::moduleHandler()->install(array('entity_cache_test'));
    $entity_type = \Drupal::state()->get('entity_cache_test');
    $this->assertEqual($entity_type->getLabel(), 'Entity Cache Test', 'Entity info label is correct.');
    $this->assertEqual($entity_type->getStorageClass(), 'Drupal\Core\Entity\EntityDatabaseStorage', 'Entity controller class info is correct.');
  }
}
