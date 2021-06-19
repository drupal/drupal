<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Prophecy\Argument;

/**
 * Tests defining bundles on entities.
 *
 * @group Entity
 */
class EntityBundleTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entity_test'];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('entity_test_class_bundles');
    $this->moduleHandler = $this->container->get('module_handler');
    $this->database = $this->container->get('database');
  }

  /**
   * Tests the bundle info defined in the entity class's bundleDefinitions().
   */
  public function testEntityClassBundles() {
    $entity_type_bundle_info = $this->container->get('entity_type.bundle.info');

    // We have to explicitly set something in the state key, as otherwise
    // entity_test_entity_bundle_info() provides a default, which we don't want.
    \Drupal::state()->set('entity_test_class_bundles.bundles', []);

    $entity_type_bundle_info->clearCachedBundles();
    $bundle_info = $entity_type_bundle_info->getBundleInfo('entity_test_class_bundles');

    $this->assertCount(1, $bundle_info);
    $this->assertArrayHasKey('alpha', $bundle_info);
    $this->assertEquals('Alpha', $bundle_info['alpha']['label']);

    // Define a bundle in hook_entity_bundle_info(); check that the hook
    // definition is merged with the class method definition.
    entity_test_create_bundle('beta', 'Beta', 'entity_test_class_bundles');

    $entity_type_bundle_info->clearCachedBundles();
    $bundle_info = $entity_type_bundle_info->getBundleInfo('entity_test_class_bundles');

    $this->assertCount(2, $bundle_info);
    $this->assertArrayHasKey('alpha', $bundle_info);
    $this->assertEquals('Alpha', $bundle_info['alpha']['label']);
    $this->assertArrayHasKey('beta', $bundle_info);
    $this->assertEquals('Beta', $bundle_info['beta']['label']);
  }

}
