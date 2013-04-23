<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityManagerTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests methods on the entity manager.
 *
 * @see \Drupal\Core\Entity\EntityManager
 */
class EntityManagerTest extends EntityUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Entity Manager',
      'description' => 'Tests methods on the entity manager.',
      'group' => 'Entity API',
    );
  }

  /**
   * Tests some methods on the manager.
   */
  public function testMethods() {
    // Tests the has controller method.
    $entity_manager = $this->container->get('plugin.manager.entity');

    $this->assertFalse($entity_manager->hasController('non_existent', 'storage'), 'A non existent entity type has no controller.');
    $this->assertFalse($entity_manager->hasController('non_existent', 'non_existent'), 'A non existent entity type has no controller.');

    $this->assertFalse($entity_manager->hasController('entity_test', 'non_existent'), 'An existent entity type does not have a non existent controller.');
    $this->assertFalse($entity_manager->hasController('entity_test', 'render'), 'The test entity does not have specified the render controller.');

    $this->assertTrue($entity_manager->hasController('entity_test', 'storage'), 'The test entity has specified the controller class');
  }

}
