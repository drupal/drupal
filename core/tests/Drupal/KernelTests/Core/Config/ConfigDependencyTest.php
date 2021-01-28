<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests for configuration dependencies.
 *
 * @coversDefaultClass \Drupal\Core\Config\ConfigManager
 *
 * @group config
 */
class ConfigDependencyTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * The entity_test module is enabled to provide content entity types.
   *
   * @var array
   */
  protected static $modules = ['config_test', 'entity_test', 'user'];

  /**
   * Tests that calculating dependencies for system module.
   */
  public function testNonEntity() {
    $this->installConfig(['system']);
    $config_manager = \Drupal::service('config.manager');
    $dependents = $config_manager->findConfigEntityDependents('module', ['system']);
    $this->assertTrue(isset($dependents['system.site']), 'Simple configuration system.site has a UUID key even though it is not a configuration entity and therefore is found when looking for dependencies of the System module.');
    // Ensure that calling
    // \Drupal\Core\Config\ConfigManager::findConfigEntityDependentsAsEntities()
    // does not try to load system.site as an entity.
    $config_manager->findConfigEntityDependentsAsEntities('module', ['system']);
  }

  /**
   * Tests creating dependencies on configuration entities.
   */
  public function testDependencyManagement() {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');
    $storage = $this->container->get('entity_type.manager')->getStorage('config_test');
    // Test dependencies between modules.
    $entity1 = $storage->create(
      [
        'id' => 'entity1',
        'dependencies' => [
          'enforced' => [
            'module' => ['node'],
          ],
        ],
      ]
    );
    $entity1->save();

    $dependents = $config_manager->findConfigEntityDependents('module', ['node']);
    $this->assertTrue(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 has a dependency on the Node module.');
    $dependents = $config_manager->findConfigEntityDependents('module', ['config_test']);
    $this->assertTrue(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 has a dependency on the config_test module.');
    $dependents = $config_manager->findConfigEntityDependents('module', ['views']);
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on the Views module.');
    // Ensure that the provider of the config entity is not actually written to
    // the dependencies array.
    $raw_config = $this->config('config_test.dynamic.entity1');
    $root_module_dependencies = $raw_config->get('dependencies.module');
    $this->assertTrue(empty($root_module_dependencies), 'Node module is not written to the root dependencies array as it is enforced.');

    // Create additional entities to test dependencies on config entities.
    $entity2 = $storage->create(['id' => 'entity2', 'dependencies' => ['enforced' => ['config' => [$entity1->getConfigDependencyName()]]]]);
    $entity2->save();
    $entity3 = $storage->create(['id' => 'entity3', 'dependencies' => ['enforced' => ['config' => [$entity2->getConfigDependencyName()]]]]);
    $entity3->save();
    $entity4 = $storage->create(['id' => 'entity4', 'dependencies' => ['enforced' => ['config' => [$entity3->getConfigDependencyName()]]]]);
    $entity4->save();

    // Test getting $entity1's dependencies as configuration dependency objects.
    $dependents = $config_manager->findConfigEntityDependents('config', [$entity1->getConfigDependencyName()]);
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on itself.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on config_test.dynamic.entity1.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on config_test.dynamic.entity1.');

    // Test getting $entity2's dependencies as entities.
    $dependents = $config_manager->findConfigEntityDependentsAsEntities('config', [$entity2->getConfigDependencyName()]);
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertNotContains('config_test:entity1', $dependent_ids, 'config_test.dynamic.entity1 does not have a dependency on config_test.dynamic.entity1.');
    $this->assertNotContains('config_test:entity2', $dependent_ids, 'config_test.dynamic.entity2 does not have a dependency on itself.');
    $this->assertContains('config_test:entity3', $dependent_ids, 'config_test.dynamic.entity3 has a dependency on config_test.dynamic.entity2.');
    $this->assertContains('config_test:entity4', $dependent_ids, 'config_test.dynamic.entity4 has a dependency on config_test.dynamic.entity2.');

    // Test getting node module's dependencies as configuration dependency
    // objects.
    $dependents = $config_manager->findConfigEntityDependents('module', ['node']);
    $this->assertTrue(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on the Node module.');

    // Test getting node module's dependencies as configuration dependency
    // objects after making $entity3 also dependent on node module but $entity1
    // no longer depend on node module.
    $entity1->setEnforcedDependencies([])->save();
    $entity3->setEnforcedDependencies(['module' => ['node'], 'config' => [$entity2->getConfigDependencyName()]])->save();
    $dependents = $config_manager->findConfigEntityDependents('module', ['node']);
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on the Node module.');
    $this->assertFalse(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 does not have a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on the Node module.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on the Node module.');

    // Test dependency on a content entity.
    $entity_test = EntityTest::create([
      'name' => $this->randomString(),
      'type' => 'entity_test',
    ]);
    $entity_test->save();
    $entity2->setEnforcedDependencies(['config' => [$entity1->getConfigDependencyName()], 'content' => [$entity_test->getConfigDependencyName()]])->save();
    $dependents = $config_manager->findConfigEntityDependents('content', [$entity_test->getConfigDependencyName()]);
    $this->assertFalse(isset($dependents['config_test.dynamic.entity1']), 'config_test.dynamic.entity1 does not have a dependency on the content entity.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity2']), 'config_test.dynamic.entity2 has a dependency on the content entity.');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity3']), 'config_test.dynamic.entity3 has a dependency on the content entity (via entity2).');
    $this->assertTrue(isset($dependents['config_test.dynamic.entity4']), 'config_test.dynamic.entity4 has a dependency on the content entity (via entity3).');

    // Create a configuration entity of a different type with the same ID as one
    // of the entities already created.
    $alt_storage = $this->container->get('entity_type.manager')->getStorage('config_query_test');
    $alt_storage->create(['id' => 'entity1', 'dependencies' => ['enforced' => ['config' => [$entity1->getConfigDependencyName()]]]])->save();
    $alt_storage->create(['id' => 'entity2', 'dependencies' => ['enforced' => ['module' => ['views']]]])->save();

    $dependents = $config_manager->findConfigEntityDependentsAsEntities('config', [$entity1->getConfigDependencyName()]);
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertNotContains('config_test:entity1', $dependent_ids, 'config_test.dynamic.entity1 does not have a dependency on itself.');
    $this->assertContains('config_test:entity2', $dependent_ids, 'config_test.dynamic.entity2 has a dependency on config_test.dynamic.entity1.');
    $this->assertContains('config_test:entity3', $dependent_ids, 'config_test.dynamic.entity3 has a dependency on config_test.dynamic.entity1.');
    $this->assertContains('config_test:entity4', $dependent_ids, 'config_test.dynamic.entity4 has a dependency on config_test.dynamic.entity1.');
    $this->assertContains('config_query_test:entity1', $dependent_ids, 'config_query_test.dynamic.entity1 has a dependency on config_test.dynamic.entity1.');
    $this->assertNotContains('config_query_test:entity2', $dependent_ids, 'config_query_test.dynamic.entity2 does not have a dependency on config_test.dynamic.entity1.');

    $dependents = $config_manager->findConfigEntityDependentsAsEntities('module', ['node', 'views']);
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertNotContains('config_test:entity1', $dependent_ids, 'config_test.dynamic.entity1 does not have a dependency on Views or Node.');
    $this->assertNotContains('config_test:entity2', $dependent_ids, 'config_test.dynamic.entity2 does not have a dependency on Views or Node.');
    $this->assertContains('config_test:entity3', $dependent_ids, 'config_test.dynamic.entity3 has a dependency on Views or Node.');
    $this->assertContains('config_test:entity4', $dependent_ids, 'config_test.dynamic.entity4 has a dependency on Views or Node.');
    $this->assertNotContains('config_query_test:entity1', $dependent_ids, 'config_test.query.entity1 does not have a dependency on Views or Node.');
    $this->assertContains('config_query_test:entity2', $dependent_ids, 'config_test.query.entity2 has a dependency on Views or Node.');

    $dependents = $config_manager->findConfigEntityDependentsAsEntities('module', ['config_test']);
    $dependent_ids = $this->getDependentIds($dependents);
    $this->assertContains('config_test:entity1', $dependent_ids, 'config_test.dynamic.entity1 has a dependency on config_test module.');
    $this->assertContains('config_test:entity2', $dependent_ids, 'config_test.dynamic.entity2 has a dependency on config_test module.');
    $this->assertContains('config_test:entity3', $dependent_ids, 'config_test.dynamic.entity3 has a dependency on config_test module.');
    $this->assertContains('config_test:entity4', $dependent_ids, 'config_test.dynamic.entity4 has a dependency on config_test module.');
    $this->assertContains('config_query_test:entity1', $dependent_ids, 'config_test.query.entity1 has a dependency on config_test module.');
    $this->assertContains('config_query_test:entity2', $dependent_ids, 'config_test.query.entity2 has a dependency on config_test module.');

    // Test the ability to find missing content dependencies.
    $missing_dependencies = $config_manager->findMissingContentDependencies();
    $this->assertEqual([], $missing_dependencies);

    $expected = [
      $entity_test->uuid() => [
        'entity_type' => 'entity_test',
        'bundle' => $entity_test->bundle(),
        'uuid' => $entity_test->uuid(),
      ],
    ];
    // Delete the content entity so that is it now missing.
    $entity_test->delete();
    $missing_dependencies = $config_manager->findMissingContentDependencies();
    $this->assertEqual($expected, $missing_dependencies);

    // Add a fake missing dependency to ensure multiple missing dependencies
    // work.
    $entity1->setEnforcedDependencies(['content' => [$entity_test->getConfigDependencyName(), 'entity_test:bundle:uuid']])->save();
    $expected['uuid'] = [
      'entity_type' => 'entity_test',
      'bundle' => 'bundle',
      'uuid' => 'uuid',
    ];
    $missing_dependencies = $config_manager->findMissingContentDependencies();
    $this->assertEqual($expected, $missing_dependencies);
  }

  /**
   * Tests ConfigManager::uninstall() and config entity dependency management.
   */
  public function testConfigEntityUninstall() {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('config_test');
    // Test dependencies between modules.
    $entity1 = $storage->create(
      [
        'id' => 'entity1',
        'dependencies' => [
          'enforced' => [
            'module' => ['node', 'config_test'],
          ],
        ],
      ]
    );
    $entity1->save();
    $entity2 = $storage->create(
      [
        'id' => 'entity2',
        'dependencies' => [
          'enforced' => [
            'config' => [$entity1->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity2->save();
    // Test that doing a config uninstall of the node module deletes entity2
    // since it is dependent on entity1 which is dependent on the node module.
    $config_manager->uninstall('module', 'node');
    $this->assertNull($storage->load('entity1'), 'Entity 1 deleted');
    $this->assertNull($storage->load('entity2'), 'Entity 2 deleted');
  }

  /**
   * Data provider for self::testConfigEntityUninstallComplex().
   */
  public function providerConfigEntityUninstallComplex() {
    // Ensure that alphabetical order has no influence on dependency fixing and
    // removal.
    return [
      [['a', 'b', 'c', 'd', 'e']],
      [['e', 'd', 'c', 'b', 'a']],
      [['e', 'c', 'd', 'a', 'b']],
    ];
  }

  /**
   * Tests complex configuration entity dependency handling during uninstall.
   *
   * Configuration entities can be deleted or updated during module uninstall
   * because they have dependencies on the module.
   *
   * @param array $entity_id_suffixes
   *   The suffixes to add to the 4 entities created by the test.
   *
   * @dataProvider providerConfigEntityUninstallComplex
   */
  public function testConfigEntityUninstallComplex(array $entity_id_suffixes) {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('config_test');
    // Entity 1 will be deleted because it depends on node.
    $entity_1 = $storage->create(
      [
        'id' => 'entity_' . $entity_id_suffixes[0],
        'dependencies' => [
          'enforced' => [
            'module' => ['node', 'config_test'],
          ],
        ],
      ]
    );
    $entity_1->save();

    // Entity 2 has a dependency on entity 1 but it can be fixed because
    // \Drupal\config_test\Entity::onDependencyRemoval() will remove the
    // dependency before config entities are deleted.
    $entity_2 = $storage->create(
      [
        'id' => 'entity_' . $entity_id_suffixes[1],
        'dependencies' => [
          'enforced' => [
            'config' => [$entity_1->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity_2->save();

    // Entity 3 will be unchanged because it is dependent on entity 2 which can
    // be fixed. The ConfigEntityInterface::onDependencyRemoval() method will
    // not be called for this entity.
    $entity_3 = $storage->create(
      [
        'id' => 'entity_' . $entity_id_suffixes[2],
        'dependencies' => [
          'enforced' => [
            'config' => [$entity_2->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity_3->save();

    // Entity 4's config dependency will be fixed but it will still be deleted
    // because it also depends on the node module.
    $entity_4 = $storage->create(
      [
        'id' => 'entity_' . $entity_id_suffixes[3],
        'dependencies' => [
          'enforced' => [
            'config' => [$entity_1->getConfigDependencyName()],
            'module' => ['node', 'config_test'],
          ],
        ],
      ]
    );
    $entity_4->save();

    // Entity 5 will be fixed because it is dependent on entity 3, which is
    // unchanged, and entity 1 which will be fixed because
    // \Drupal\config_test\Entity::onDependencyRemoval() will remove the
    // dependency.
    $entity_5 = $storage->create(
      [
        'id' => 'entity_' . $entity_id_suffixes[4],
        'dependencies' => [
          'enforced' => [
            'config' => [
              $entity_1->getConfigDependencyName(),
              $entity_3->getConfigDependencyName(),
            ],
          ],
        ],
      ]
    );
    $entity_5->save();

    // Set a more complicated test where dependencies will be fixed.
    \Drupal::state()->set('config_test.fix_dependencies', [$entity_1->getConfigDependencyName()]);
    \Drupal::state()->set('config_test.on_dependency_removal_called', []);

    // Do a dry run using
    // \Drupal\Core\Config\ConfigManager::getConfigEntitiesToChangeOnDependencyRemoval().
    $config_entities = $config_manager->getConfigEntitiesToChangeOnDependencyRemoval('module', ['node']);

    // Assert that \Drupal\config_test\Entity\ConfigTest::onDependencyRemoval()
    // is called as expected and with the correct dependencies.
    $called = \Drupal::state()->get('config_test.on_dependency_removal_called', []);
    $this->assertArrayNotHasKey($entity_3->id(), $called, 'ConfigEntityInterface::onDependencyRemoval() is not called for entity 3.');
    $this->assertSame([$entity_1->id(), $entity_4->id(), $entity_2->id(), $entity_5->id()], array_keys($called), 'The most dependent entities have ConfigEntityInterface::onDependencyRemoval() called first.');
    $this->assertSame(['config' => [], 'content' => [], 'module' => ['node'], 'theme' => []], $called[$entity_1->id()]);
    $this->assertSame(['config' => [$entity_1->getConfigDependencyName()], 'content' => [], 'module' => [], 'theme' => []], $called[$entity_2->id()]);
    $this->assertSame(['config' => [$entity_1->getConfigDependencyName()], 'content' => [], 'module' => ['node'], 'theme' => []], $called[$entity_4->id()]);
    $this->assertSame(['config' => [$entity_1->getConfigDependencyName()], 'content' => [], 'module' => [], 'theme' => []], $called[$entity_5->id()]);

    $this->assertEqual($entity_1->uuid(), $config_entities['delete'][1]->uuid(), 'Entity 1 will be deleted.');
    $this->assertEqual($entity_2->uuid(), $config_entities['update'][0]->uuid(), 'Entity 2 will be updated.');
    $this->assertEqual($entity_3->uuid(), reset($config_entities['unchanged'])->uuid(), 'Entity 3 is not changed.');
    $this->assertEqual($entity_4->uuid(), $config_entities['delete'][0]->uuid(), 'Entity 4 will be deleted.');
    $this->assertEqual($entity_5->uuid(), $config_entities['update'][1]->uuid(), 'Entity 5 is updated.');

    // Perform the uninstall.
    $config_manager->uninstall('module', 'node');

    // Test that expected actions have been performed.
    $this->assertNull($storage->load($entity_1->id()), 'Entity 1 deleted');
    $entity_2 = $storage->load($entity_2->id());
    $this->assertNotEmpty($entity_2, 'Entity 2 not deleted');
    $this->assertEqual([], $entity_2->calculateDependencies()->getDependencies()['config'], 'Entity 2 dependencies updated to remove dependency on entity 1.');
    $entity_3 = $storage->load($entity_3->id());
    $this->assertNotEmpty($entity_3, 'Entity 3 not deleted');
    $this->assertEqual([$entity_2->getConfigDependencyName()], $entity_3->calculateDependencies()->getDependencies()['config'], 'Entity 3 still depends on entity 2.');
    $this->assertNull($storage->load($entity_4->id()), 'Entity 4 deleted');
  }

  /**
   * @covers ::uninstall
   * @covers ::getConfigEntitiesToChangeOnDependencyRemoval
   */
  public function testConfigEntityUninstallThirdParty() {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('config_test');
    // Entity 1 will be fixed because it only has a dependency via third-party
    // settings, which are fixable.
    $entity_1 = $storage->create([
      'id' => 'entity_1',
      'dependencies' => [
        'enforced' => [
          'module' => ['config_test'],
        ],
      ],
      'third_party_settings' => [
        'node' => [
          'foo' => 'bar',
        ],
      ],
    ]);
    $entity_1->save();

    // Entity 2 has a dependency on entity 1.
    $entity_2 = $storage->create([
      'id' => 'entity_2',
      'dependencies' => [
        'enforced' => [
          'config' => [$entity_1->getConfigDependencyName()],
        ],
      ],
      'third_party_settings' => [
        'node' => [
          'foo' => 'bar',
        ],
      ],
    ]);
    $entity_2->save();

    // Entity 3 will be unchanged because it is dependent on entity 2 which can
    // be fixed. The ConfigEntityInterface::onDependencyRemoval() method will
    // not be called for this entity.
    $entity_3 = $storage->create([
      'id' => 'entity_3',
      'dependencies' => [
        'enforced' => [
          'config' => [$entity_2->getConfigDependencyName()],
        ],
      ],
    ]);
    $entity_3->save();

    // Entity 4's config dependency will be fixed but it will still be deleted
    // because it also depends on the node module.
    $entity_4 = $storage->create([
      'id' => 'entity_4',
      'dependencies' => [
        'enforced' => [
          'config' => [$entity_1->getConfigDependencyName()],
          'module' => ['node', 'config_test'],
        ],
      ],
    ]);
    $entity_4->save();

    \Drupal::state()->set('config_test.fix_dependencies', []);
    \Drupal::state()->set('config_test.on_dependency_removal_called', []);

    // Do a dry run using
    // \Drupal\Core\Config\ConfigManager::getConfigEntitiesToChangeOnDependencyRemoval().
    $config_entities = $config_manager->getConfigEntitiesToChangeOnDependencyRemoval('module', ['node']);
    $config_entity_ids = [
      'update' => [],
      'delete' => [],
      'unchanged' => [],
    ];
    foreach ($config_entities as $type => $config_entities_by_type) {
      foreach ($config_entities_by_type as $config_entity) {
        $config_entity_ids[$type][] = $config_entity->id();
      }
    }
    $expected = [
      'update' => [$entity_1->id(), $entity_2->id()],
      'delete' => [$entity_4->id()],
      'unchanged' => [$entity_3->id()],
    ];
    $this->assertSame($expected, $config_entity_ids);

    $called = \Drupal::state()->get('config_test.on_dependency_removal_called', []);
    $this->assertArrayNotHasKey($entity_3->id(), $called, 'ConfigEntityInterface::onDependencyRemoval() is not called for entity 3.');
    $this->assertSame([$entity_1->id(), $entity_4->id(), $entity_2->id()], array_keys($called), 'The most dependent entities have ConfigEntityInterface::onDependencyRemoval() called first.');
    $this->assertSame(['config' => [], 'content' => [], 'module' => ['node'], 'theme' => []], $called[$entity_1->id()]);
    $this->assertSame(['config' => [], 'content' => [], 'module' => ['node'], 'theme' => []], $called[$entity_2->id()]);
    $this->assertSame(['config' => [], 'content' => [], 'module' => ['node'], 'theme' => []], $called[$entity_4->id()]);

    // Perform the uninstall.
    $config_manager->uninstall('module', 'node');

    // Test that expected actions have been performed.
    $entity_1 = $storage->load($entity_1->id());
    $this->assertNotEmpty($entity_1, 'Entity 1 not deleted');
    $this->assertSame($entity_1->getThirdPartySettings('node'), [], 'Entity 1 third party settings updated.');
    $entity_2 = $storage->load($entity_2->id());
    $this->assertNotEmpty($entity_2, 'Entity 2 not deleted');
    $this->assertSame($entity_2->getThirdPartySettings('node'), [], 'Entity 2 third party settings updated.');
    $this->assertSame($entity_2->calculateDependencies()->getDependencies()['config'], [$entity_1->getConfigDependencyName()], 'Entity 2 still depends on entity 1.');
    $entity_3 = $storage->load($entity_3->id());
    $this->assertNotEmpty($entity_3, 'Entity 3 not deleted');
    $this->assertSame($entity_3->calculateDependencies()->getDependencies()['config'], [$entity_2->getConfigDependencyName()], 'Entity 3 still depends on entity 2.');
    $this->assertNull($storage->load($entity_4->id()), 'Entity 4 deleted');
  }

  /**
   * Tests deleting a configuration entity and dependency management.
   */
  public function testConfigEntityDelete() {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('config_test');
    // Test dependencies between configuration entities.
    $entity1 = $storage->create(
      [
        'id' => 'entity1',
      ]
    );
    $entity1->save();
    $entity2 = $storage->create(
      [
        'id' => 'entity2',
        'dependencies' => [
          'enforced' => [
            'config' => [$entity1->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity2->save();

    // Do a dry run using
    // \Drupal\Core\Config\ConfigManager::getConfigEntitiesToChangeOnDependencyRemoval().
    $config_entities = $config_manager->getConfigEntitiesToChangeOnDependencyRemoval('config', [$entity1->getConfigDependencyName()]);
    $this->assertEqual($entity2->uuid(), reset($config_entities['delete'])->uuid(), 'Entity 2 will be deleted.');
    $this->assertTrue(empty($config_entities['update']), 'No dependent configuration entities will be updated.');
    $this->assertTrue(empty($config_entities['unchanged']), 'No dependent configuration entities will be unchanged.');

    // Test that doing a delete of entity1 deletes entity2 since it is dependent
    // on entity1.
    $entity1->delete();
    $this->assertNull($storage->load('entity1'), 'Entity 1 deleted');
    $this->assertNull($storage->load('entity2'), 'Entity 2 deleted');

    // Set a more complicated test where dependencies will be fixed.
    \Drupal::state()->set('config_test.fix_dependencies', [$entity1->getConfigDependencyName()]);

    // Entity1 will be deleted by the test.
    $entity1 = $storage->create(
      [
        'id' => 'entity1',
      ]
    );
    $entity1->save();

    // Entity2 has a dependency on Entity1 but it can be fixed because
    // \Drupal\config_test\Entity::onDependencyRemoval() will remove the
    // dependency before config entities are deleted.
    $entity2 = $storage->create(
      [
        'id' => 'entity2',
        'dependencies' => [
          'enforced' => [
            'config' => [$entity1->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity2->save();

    // Entity3 will be unchanged because it is dependent on Entity2 which can
    // be fixed.
    $entity3 = $storage->create(
      [
        'id' => 'entity3',
        'dependencies' => [
          'enforced' => [
            'config' => [$entity2->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity3->save();

    // Do a dry run using
    // \Drupal\Core\Config\ConfigManager::getConfigEntitiesToChangeOnDependencyRemoval().
    $config_entities = $config_manager->getConfigEntitiesToChangeOnDependencyRemoval('config', [$entity1->getConfigDependencyName()]);
    $this->assertTrue(empty($config_entities['delete']), 'No dependent configuration entities will be deleted.');
    $this->assertEqual($entity2->uuid(), reset($config_entities['update'])->uuid(), 'Entity 2 will be updated.');
    $this->assertEqual($entity3->uuid(), reset($config_entities['unchanged'])->uuid(), 'Entity 3 is not changed.');

    // Perform the uninstall.
    $entity1->delete();

    // Test that expected actions have been performed.
    $this->assertNull($storage->load('entity1'), 'Entity 1 deleted');
    $entity2 = $storage->load('entity2');
    $this->assertNotEmpty($entity2, 'Entity 2 not deleted');
    $this->assertEqual([], $entity2->calculateDependencies()->getDependencies()['config'], 'Entity 2 dependencies updated to remove dependency on Entity1.');
    $entity3 = $storage->load('entity3');
    $this->assertNotEmpty($entity3, 'Entity 3 not deleted');
    $this->assertEqual($entity3->calculateDependencies()->getDependencies()['config'], [$entity2->getConfigDependencyName()], 'Entity 3 still depends on Entity 2.');
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
    $storage = $this->container->get('entity_type.manager')->getStorage('config_test');
    $entity1 = $storage->create(
      [
        'id' => 'entity1',
        'dependencies' => [
          'enforced' => [
            'content' => [$content_entity->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity1->save();
    $entity2 = $storage->create(
      [
        'id' => 'entity2',
        'dependencies' => [
          'enforced' => [
            'config' => [$entity1->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity2->save();

    // Create a configuration entity that is not in the dependency chain.
    $entity3 = $storage->create(['id' => 'entity3']);
    $entity3->save();

    $config_entities = $config_manager->getConfigEntitiesToChangeOnDependencyRemoval('content', [$content_entity->getConfigDependencyName()]);
    $this->assertEqual($entity1->uuid(), $config_entities['delete'][1]->uuid(), 'Entity 1 will be deleted.');
    $this->assertEqual($entity2->uuid(), $config_entities['delete'][0]->uuid(), 'Entity 2 will be deleted.');
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
    $dependent_ids = [];
    foreach ($dependents as $dependent) {
      $dependent_ids[] = $dependent->getEntityTypeId() . ':' . $dependent->id();
    }
    return $dependent_ids;
  }

}
