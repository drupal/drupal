<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityManagerTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests methods on the entity manager.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface
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
    $entity_manager = $this->container->get('entity.manager');
    $this->assertEqual(spl_object_hash($entity_manager), spl_object_hash($this->container->get('entity.manager')));

    $this->assertFalse($entity_manager->hasController('non_existent', 'storage'), 'A non existent entity type has no controller.');
    $this->assertFalse($entity_manager->hasController('non_existent', 'non_existent'), 'A non existent entity type has no controller.');

    $this->assertFalse($entity_manager->hasController('entity_test', 'non_existent'), 'An existent entity type does not have a non existent controller.');
    $this->assertFalse($entity_manager->hasController('entity_test_mul', 'view_builder'), 'The test entity does not have specified the view builder.');

    $this->assertTrue($entity_manager->hasController('entity_test', 'storage'), 'The test entity has specified the controller class');
  }

}
