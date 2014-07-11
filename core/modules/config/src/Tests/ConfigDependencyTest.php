<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigDependencyTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests for configuration dependencies.
 *
 * @group config
 */
class ConfigDependencyTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'config_test');

  /**
   * Tests that calculating dependencies for system module.
   */
  public function testNonEntity() {
    $this->installConfig(array('system'));
    $config_manager = \Drupal::service('config.manager');
    $dependents = $config_manager->findConfigEntityDependents('module', array('system'));
    $this->assertTrue(isset($dependents['system.site']), 'Simple configuration system.site has a UUID key even though it is not a configuration entity and therefore is found when looking for dependencies of the System module.');
    // Ensure that calling
    // \Drupal\Core\Config\ConfigManager::findConfigEntityDependentsAsEntities()
    // does not try to load system.site as an entity.
    $config_manager->findConfigEntityDependentsAsEntities('module', array('system'));
  }

  /**
   * Tests creating dependencies on configuration entities.
   */
  public function testDependencyMangement() {
    $config_manager = \Drupal::service('config.manager');
    $storage = $this->container->get('entity.manager')->getStorage('config_test');
    // Test dependencies between modules.
    $entity1 = $storage->create(
      array(
        'id' => 'entity1',
        'test_dependencies' => array(
          'module' => array('node', 'config_test')
        )
      )
    );
    $entity1->save();

    $dependents = $config_manager->findConfigEntityDependents('module', array('node'));
    $this->assertTrue(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 has a dependency on the Node module.');
    $dependents = $config_manager->findConfigEntityDependents('module', array('config_test'));
    $this->assertTrue(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 has a dependency on the config_test module.');
    $dependents = $config_manager->findConfigEntityDependents('module', array('views'));
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on the Views module.');
    // Ensure that the provider of the config entity is not actually written to
    // the dependencies array.
    $raw_config = \Drupal::config('config_test.dynamic.entity1');
    $this->assertTrue(array_search('config_test', $raw_config->get('dependencies.module')) === FALSE, 'Module that the provides the configuration entity is not written to the dependencies array as this is implicit.');
    $this->assertTrue(array_search('node', $raw_config->get('dependencies.module')) !== FALSE, 'Node module is written to the dependencies array as this has to be explicit.');

    // Create additional entities to test dependencies on config entities.
    $entity2 = $storage->create(array('id' => 'entity2', 'test_dependencies' => array('entity' => array($entity1->getConfigDependencyName()))));
    $entity2->save();
    $entity3 = $storage->create(array('id' => 'entity3', 'test_dependencies' => array('entity' => array($entity2->getConfigDependencyName()))));
    $entity3->save();
    $entity4 = $storage->create(array('id' => 'entity4', 'test_dependencies' => array('entity' => array($entity3->getConfigDependencyName()))));
    $entity4->save();

    // Test getting $entity1's dependencies as configuration dependency objects.
    $dependents = $config_manager->findConfigEntityDependents('entity', array($entity1->getConfigDependencyName()));
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on itself.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on config_test.dynamic.entity1.');

    // Test getting $entity2's dependencies as entities.
    $dependents = $config_manager->findConfigEntityDependentsAsEntities('entity', array($entity2->getConfigDependencyName()));
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertFalse(in_array('config_test:entity1', $dependent_ids), 'config_test.dynamic.entity1 does not have a dependency on config_test.dynamic.entity1.');
    $this->assertFalse(in_array('config_test:entity2', $dependent_ids), 'config_test.dynamic.entity2 does not have a dependency on itself.');
    $this->assertTrue(in_array('config_test:entity3', $dependent_ids), 'config_test.dynamic.entity3 has a dependency on config_test.dynamic.entity2.');
    $this->assertTrue(in_array('config_test:entity4', $dependent_ids), 'config_test.dynamic.entity4 has a dependency on config_test.dynamic.entity2.');

    // Test getting node module's dependencies as configuration dependency
    // objects.
    $dependents = $config_manager->findConfigEntityDependents('module', array('node'));
    $this->assertTrue(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on the Node module.');

    // Test getting node module's dependencies as configuration dependency
    // objects after making $entity3 also dependent on node module but $entity1
    // no longer depend on node module.
    $entity1->test_dependencies = array();
    $entity1->save();
    $entity3->test_dependencies['module'] = array('node');
    $entity3->save();
    $dependents = $config_manager->findConfigEntityDependents('module', array('node'));
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on the Node module.');
    $this->assertFalse(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 does not have a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on the Node module.');

    // Create a configuration entity of a different type with the same ID as one
    // of the entities already created.
    $alt_storage = $this->container->get('entity.manager')->getStorage('config_query_test');
    $alt_storage->create(array('id' => 'entity1', 'test_dependencies' => array('entity' => array($entity1->getConfigDependencyName()))))->save();
    $alt_storage->create(array('id' => 'entity2', 'test_dependencies' => array('module' => array('views'))))->save();

    $dependents = $config_manager->findConfigEntityDependentsAsEntities('entity', array($entity1->getConfigDependencyName()));
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertFalse(in_array('config_test:entity1', $dependent_ids), 'config_test.dynamic.entity1 does not have a dependency on itself.');
    $this->assertTrue(in_array('config_test:entity2', $dependent_ids), 'config_test.dynamic.entity2 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(in_array('config_test:entity3', $dependent_ids), 'config_test.dynamic.entity3 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(in_array('config_test:entity3', $dependent_ids), 'config_test.dynamic.entity4 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(in_array('config_query_test:entity1', $dependent_ids), 'config_query_test.dynamic.entity1 has a dependency on config_test.dynamic.entity1.');
    $this->assertFalse(in_array('config_query_test:entity2', $dependent_ids), 'config_query_test.dynamic.entity2 does not have a dependency on config_test.dynamic.entity1.');

    $dependents = $config_manager->findConfigEntityDependentsAsEntities('module', array('node', 'views'));
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertFalse(in_array('config_test:entity1', $dependent_ids), 'config_test.dynamic.entity1 does not have a dependency on Views or Node.');
    $this->assertFalse(in_array('config_test:entity2', $dependent_ids), 'config_test.dynamic.entity2 does not have a dependency on Views or Node.');
    $this->assertTrue(in_array('config_test:entity3', $dependent_ids), 'config_test.dynamic.entity3 has a dependency on Views or Node.');
    $this->assertTrue(in_array('config_test:entity4', $dependent_ids), 'config_test.dynamic.entity4 has a dependency on Views or Node.');
    $this->assertFalse(in_array('config_query_test:entity1', $dependent_ids), 'config_test.query.entity1 does not have a dependency on Views or Node.');
    $this->assertTrue(in_array('config_query_test:entity2', $dependent_ids), 'config_test.query.entity2 has a dependency on Views or Node.');

    $dependents = $config_manager->findConfigEntityDependentsAsEntities('module', array('config_test'));
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertTrue(in_array('config_test:entity1', $dependent_ids), 'config_test.dynamic.entity1 has a dependency on config_test module.');
    $this->assertTrue(in_array('config_test:entity2', $dependent_ids), 'config_test.dynamic.entity2 has a dependency on config_test module.');
    $this->assertTrue(in_array('config_test:entity3', $dependent_ids), 'config_test.dynamic.entity3 has a dependency on config_test module.');
    $this->assertTrue(in_array('config_test:entity4', $dependent_ids), 'config_test.dynamic.entity4 has a dependency on config_test module.');
    $this->assertTrue(in_array('config_query_test:entity1', $dependent_ids), 'config_test.query.entity1 has a dependency on config_test module.');
    $this->assertTrue(in_array('config_query_test:entity2', $dependent_ids), 'config_test.query.entity2 has a dependency on config_test module.');

  }

  /**
   * Gets a list of identifiers from an array of configuration entities.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface[] $dependents
   *   An array of configuration entities.
   *
   * @return array
   *   An array with values of entity_type_id:ID
   */
  protected function getDependentIds(array $dependents) {
    $dependent_ids = array();
    foreach($dependents as $dependent) {
      $dependent_ids[] = $dependent->getEntityTypeId() . ':' . $dependent->id();
    }
    return $dependent_ids;
  }
}
