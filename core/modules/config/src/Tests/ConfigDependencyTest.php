<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigDependencyTest.
 */

namespace Drupal\config\Tests;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests for configuration dependencies.
 *
 * @group config
 */
class ConfigDependencyTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'config_test', 'entity_test', 'user');

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
        'dependencies' => array(
          'enforced' => array(
            'module' => array('node')
          )
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
    $raw_config = $this->config('config_test.dynamic.entity1');
    $this->assertTrue(array_search('node', $raw_config->get('dependencies.module')) !== FALSE, 'Node module is written to the dependencies array as this has to be explicit.');

    // Create additional entities to test dependencies on config entities.
    $entity2 = $storage->create(array('id' => 'entity2', 'dependencies' => array('enforced' => array('config' => array($entity1->getConfigDependencyName())))));
    $entity2->save();
    $entity3 = $storage->create(array('id' => 'entity3', 'dependencies' => array('enforced' => array('config' => array($entity2->getConfigDependencyName())))));
    $entity3->save();
    $entity4 = $storage->create(array('id' => 'entity4', 'dependencies' => array('enforced' => array('config' => array($entity3->getConfigDependencyName())))));
    $entity4->save();

    // Test getting $entity1's dependencies as configuration dependency objects.
    $dependents = $config_manager->findConfigEntityDependents('config', array($entity1->getConfigDependencyName()));
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on itself.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on config_test.dynamic.entity1.');

    // Test getting $entity2's dependencies as entities.
    $dependents = $config_manager->findConfigEntityDependentsAsEntities('config', array($entity2->getConfigDependencyName()));
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
    $entity1->setEnforcedDependencies([])->save();
    $entity3->setEnforcedDependencies(['module' => ['node'], 'config' => [$entity2->getConfigDependencyName()]])->save();
    $dependents = $config_manager->findConfigEntityDependents('module', array('node'));
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on the Node module.');
    $this->assertFalse(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 does not have a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on the Node module.');

    // Test dependency on a fake content entity.
    $entity2->setEnforcedDependencies(['config' => [$entity1->getConfigDependencyName()], 'content' => ['node:page:uuid']])->save();;
    $dependents = $config_manager->findConfigEntityDependents('content', array('node:page:uuid'));
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on the content entity.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 has a dependency on the content entity.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on the content entity (via entity2).');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on the content entity (via entity3).');

    // Create a configuration entity of a different type with the same ID as one
    // of the entities already created.
    $alt_storage = $this->container->get('entity.manager')->getStorage('config_query_test');
    $alt_storage->create(array('id' => 'entity1', 'dependencies' => array('enforced' => array('config' => array($entity1->getConfigDependencyName())))))->save();
    $alt_storage->create(array('id' => 'entity2', 'dependencies' => array('enforced' => array('module' => array('views')))))->save();

    $dependents = $config_manager->findConfigEntityDependentsAsEntities('config', array($entity1->getConfigDependencyName()));
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertFalse(in_array('config_test:entity1', $dependent_ids), 'config_test.dynamic.entity1 does not have a dependency on itself.');
    $this->assertTrue(in_array('config_test:entity2', $dependent_ids), 'config_test.dynamic.entity2 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(in_array('config_test:entity3', $dependent_ids), 'config_test.dynamic.entity3 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(in_array('config_test:entity4', $dependent_ids), 'config_test.dynamic.entity4 has a dependency on config_test.dynamic.entity1.');
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
   * Tests ConfigManager::uninstall() and config entity dependency management.
   */
  public function testConfigEntityUninstall() {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->container->get('entity.manager')->getStorage('config_test');
    // Test dependencies between modules.
    $entity1 = $storage->create(
      array(
        'id' => 'entity1',
        'dependencies' => array(
          'enforced' => array(
            'module' => array('node', 'config_test')
          ),
        ),
      )
    );
    $entity1->save();
    $entity2 = $storage->create(
      array(
        'id' => 'entity2',
        'dependencies' => array(
          'enforced' => array(
            'config' => array($entity1->getConfigDependencyName()),
          ),
        ),
      )
    );
    $entity2->save();
    // Test that doing a config uninstall of the node module deletes entity2
    // since it is dependent on entity1 which is dependent on the node module.
    $config_manager->uninstall('module', 'node');
    $this->assertFalse($storage->load('entity1'), 'Entity 1 deleted');
    $this->assertFalse($storage->load('entity2'), 'Entity 2 deleted');

    // Set a more complicated test where dependencies will be fixed.
    \Drupal::state()->set('config_test.fix_dependencies', array($entity1->getConfigDependencyName()));

    // Entity1 will be deleted because it depends on node.
    $entity1 = $storage->create(
      array(
        'id' => 'entity1',
        'dependencies' => array(
          'enforced' => array(
            'module' => array('node', 'config_test')
          ),
        ),
      )
    );
    $entity1->save();

    // Entity2 has a dependency on Entity1 but it can be fixed because
    // \Drupal\config_test\Entity::onDependencyRemoval() will remove the
    // dependency before config entities are deleted.
    $entity2 = $storage->create(
      array(
        'id' => 'entity2',
        'dependencies' => array(
          'enforced' => array(
            'config' => array($entity1->getConfigDependencyName()),
          ),
        ),
      )
    );
    $entity2->save();

    // Entity3 will be unchanged because it is dependent on Entity2 which can
    // be fixed.
    $entity3 = $storage->create(
      array(
        'id' => 'entity3',
        'dependencies' => array(
          'enforced' => array(
            'config' => array($entity2->getConfigDependencyName()),
          ),
        ),
      )
    );
    $entity3->save();

    // Entity4's config dependency will be fixed but it will still be deleted
    // because it also depends on the node module.
    $entity4 = $storage->create(
      array(
        'id' => 'entity4',
        'dependencies' => array(
          'enforced' => array(
            'config' => array($entity1->getConfigDependencyName()),
            'module' => array('node', 'config_test')
          ),
        ),
      )
    );
    $entity4->save();

    // Do a dry run using
    // \Drupal\Core\Config\ConfigManager::getConfigEntitiesToChangeOnDependencyRemoval().
    $config_entities = $config_manager->getConfigEntitiesToChangeOnDependencyRemoval('module', ['node']);
    $this->assertEqual($entity1->uuid(), $config_entities['delete'][0]->uuid(), 'Entity 1 will be deleted.');
    $this->assertEqual($entity2->uuid(), reset($config_entities['update'])->uuid(), 'Entity 2 will be updated.');
    $this->assertEqual($entity3->uuid(), reset($config_entities['unchanged'])->uuid(), 'Entity 3 is not changed.');
    $this->assertEqual($entity4->uuid(), $config_entities['delete'][1]->uuid(), 'Entity 4 will be deleted.');

    // Perform the uninstall.
    $config_manager->uninstall('module', 'node');

    // Test that expected actions have been performed.
    $this->assertFalse($storage->load('entity1'), 'Entity 1 deleted');
    $entity2 = $storage->load('entity2');
    $this->assertTrue($entity2, 'Entity 2 not deleted');
    $this->assertEqual($entity2->calculateDependencies()['config'], array(), 'Entity 2 dependencies updated to remove dependency on Entity1.');
    $entity3 = $storage->load('entity3');
    $this->assertTrue($entity3, 'Entity 3 not deleted');
    $this->assertEqual($entity3->calculateDependencies()['config'], [$entity2->getConfigDependencyName()], 'Entity 3 still depends on Entity 2.');
    $this->assertFalse($storage->load('entity4'), 'Entity 4 deleted');
  }

  /**
   * Tests getConfigEntitiesToChangeOnDependencyRemoval() with content entities.
   *
   * At the moment there is no runtime code that calculates configuration
   * dependencies on content entity delete because this calculation is expensive
   * and all content dependencies are soft. This test ensures that the code
   * works for content entities.
   *
   * @see \Drupal\Core\Config\ConfigManager::getConfigEntitiesToChangeOnDependencyRemoval()
   */
  public function testContentEntityDelete() {
    $this->installEntitySchema('entity_test');
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');

    $content_entity = EntityTest::create();
    $content_entity->save();
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->container->get('entity.manager')->getStorage('config_test');
    $entity1 = $storage->create(
      array(
        'id' => 'entity1',
        'dependencies' => array(
          'enforced' => array(
            'content' => array($content_entity->getConfigDependencyName())
          ),
        ),
      )
    );
    $entity1->save();
    $entity2 = $storage->create(
      array(
        'id' => 'entity2',
        'dependencies' => array(
          'enforced' => array(
            'config' => array($entity1->getConfigDependencyName())
          ),
        ),
      )
    );
    $entity2->save();

    // Create a configuration entity that is not in the dependency chain.
    $entity3 = $storage->create(array('id' => 'entity3'));
    $entity3->save();

    $config_entities = $config_manager->getConfigEntitiesToChangeOnDependencyRemoval('content', [$content_entity->getConfigDependencyName()]);
    $this->assertEqual($entity1->uuid(), $config_entities['delete'][0]->uuid(), 'Entity 1 will be deleted.');
    $this->assertEqual($entity2->uuid(), $config_entities['delete'][1]->uuid(), 'Entity 2 will be deleted.');
    $this->assertTrue(empty($config_entities['update']), 'No dependencies of the content entity will be updated.');
    $this->assertTrue(empty($config_entities['unchanged']), 'No dependencies of the content entity will be unchanged.');
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
