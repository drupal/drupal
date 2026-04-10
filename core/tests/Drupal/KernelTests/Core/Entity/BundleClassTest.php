<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\Exception\AmbiguousBundleClassException;
use Drupal\Core\Entity\Exception\BundleClassInheritanceException;
use Drupal\Core\Entity\Exception\MissingBundleClassException;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\EntityTestHelper;
use Drupal\entity_test_attribute_bundle_class\Entity\EntityTestBundleClassOverrideA;
use Drupal\entity_test_attribute_bundle_class\Entity\EntityTestBundleClassOverrideB;
use Drupal\entity_test_attribute_bundle_class\Entity\EntityTestWithBundleTypeNewBundle;
use Drupal\entity_test_bundle_class\Entity\EntityTestAmbiguousBundleClass;
use Drupal\entity_test_bundle_class\Entity\EntityTestBundleClass;
use Drupal\entity_test_bundle_class\Entity\EntityTestUserClass;
use Drupal\entity_test_bundle_class\Entity\EntityTestVariant;
use Drupal\entity_test_bundle_class\Entity\SharedEntityTestBundleClassA;
use Drupal\entity_test_bundle_class\Entity\SharedEntityTestBundleClassB;
use Drupal\entity_test_attribute_bundle_class\Entity\Subdir\EntityTestSubdirBundleClass;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests entity bundle classes.
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class BundleClassTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_bundle_class'];

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Controls whether ::entityBundleInfoAlter() will alter bundle information.
   *
   * @var bool
   */
  protected bool $alterAttributeBundleInfo = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->storage = $this->entityTypeManager->getStorage('entity_test');
  }

  /**
   * Tests making use of a custom bundle class.
   */
  public function testEntitySubclass(): void {
    EntityTestHelper::createBundle('bundle_class');

    // Ensure we start life with empty counters.
    $this->assertEquals(0, EntityTestBundleClass::$preCreateCount);
    $this->assertEquals(0, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postLoadCount);
    $this->assertCount(0, EntityTestBundleClass::$postLoadEntitiesCount);

    // Verify statically created entity with bundle class returns correct class.
    $entity = EntityTestBundleClass::create();
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);

    // Verify that bundle returns bundle_class when create is called without
    // passing a bundle.
    $this->assertSame($entity->bundle(), 'bundle_class');

    // Check that both preCreate() and postCreate() were called once.
    $this->assertEquals(1, EntityTestBundleClass::$preCreateCount);
    $this->assertEquals(1, $entity->postCreateCount);
    // Verify that none of the other methods have been invoked.
    $this->assertEquals(0, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postLoadCount);
    $this->assertCount(0, EntityTestBundleClass::$postLoadEntitiesCount);

    // Verify statically created entity with bundle class returns correct
    // bundle.
    $entity = EntityTestBundleClass::create(['type' => 'custom']);
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);
    $this->assertEquals('bundle_class', $entity->bundle());

    // We should have seen preCreate() a 2nd time.
    $this->assertEquals(2, EntityTestBundleClass::$preCreateCount);
    // postCreate() is specific to each entity instance, so still 1.
    $this->assertEquals(1, $entity->postCreateCount);
    // Verify that none of the other methods have been invoked.
    $this->assertEquals(0, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postLoadCount);
    $this->assertCount(0, EntityTestBundleClass::$postLoadEntitiesCount);

    // Verify that the entity storage creates the entity using the proper class.
    $entity = $this->storage->create(['type' => 'bundle_class']);
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);

    // We should have seen preCreate() a 3rd time.
    $this->assertEquals(3, EntityTestBundleClass::$preCreateCount);
    $this->assertEquals(1, $entity->postCreateCount);
    // Nothing else has been invoked.
    $this->assertEquals(0, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postLoadCount);
    $this->assertCount(0, EntityTestBundleClass::$postLoadEntitiesCount);

    // Verify that loading an entity returns the proper class.
    $entity->save();
    $id = $entity->id();
    $this->storage->resetCache();
    $entity = $this->storage->load($id);
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);

    // Loading an existing entity shouldn't call preCreate() nor postCreate().
    $this->assertEquals(3, EntityTestBundleClass::$preCreateCount);
    $this->assertEquals(0, $entity->postCreateCount);
    // Nothing has been deleted.
    $this->assertEquals(0, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postDeleteCount);
    // We should now have seen postLoad() called once.
    $this->assertEquals(1, EntityTestBundleClass::$postLoadCount);
    // It should have been invoked with a single entity.
    $this->assertCount(1, EntityTestBundleClass::$postLoadEntitiesCount);
    $this->assertEquals(1, EntityTestBundleClass::$postLoadEntitiesCount[0]);

    // Create additional entities to test invocations during loadMultiple().
    $entity_2 = $this->storage->create(['type' => 'bundle_class']);
    $entity_2->save();
    $this->assertEquals(4, EntityTestBundleClass::$preCreateCount);

    $entity_3 = $this->storage->create(['type' => 'bundle_class']);
    $entity_3->save();
    $this->assertEquals(5, EntityTestBundleClass::$preCreateCount);

    // Make another bundle that does not have a bundle subclass.
    EntityTestHelper::createBundle('entity_test');

    $entity_test_1 = $this->storage->create(['type' => 'entity_test']);
    $entity_test_1->save();
    // EntityTestBundleClass::preCreate() should not have been called.
    $this->assertEquals(5, EntityTestBundleClass::$preCreateCount);

    $entity_test_2 = $this->storage->create(['type' => 'entity_test']);
    $entity_test_2->save();
    // EntityTestBundleClass::preCreate() should still not have been called.
    $this->assertEquals(5, EntityTestBundleClass::$preCreateCount);

    // Try calling loadMultiple().
    $entity_ids = [
      $entity->id(),
      $entity_2->id(),
      $entity_3->id(),
      $entity_test_1->id(),
      $entity_test_2->id(),
    ];
    $this->storage->loadMultiple($entity_ids);
    // postLoad() should only have been called once more so far.
    $this->assertEquals(2, EntityTestBundleClass::$postLoadCount);
    $this->assertCount(2, EntityTestBundleClass::$postLoadEntitiesCount);

    // Only 3 of the 5 entities we just loaded use the bundle class. However,
    // one of them has already been loaded and we're getting the cached entity
    // without re-invoking postLoad(). So the custom postLoad() method should
    // only have been invoked with 2 entities.
    $this->assertEquals(2, EntityTestBundleClass::$postLoadEntitiesCount[1]);

    // Reset the storage cache and try loading again.
    $this->storage->resetCache();

    $this->storage->loadMultiple($entity_ids);
    $this->assertEquals(3, EntityTestBundleClass::$postLoadCount);
    $this->assertCount(3, EntityTestBundleClass::$postLoadEntitiesCount);
    // This time, all 3 bundle_class entities should be included.
    $this->assertEquals(3, EntityTestBundleClass::$postLoadEntitiesCount[2]);

    // Start deleting things and count delete-related method invocations.
    $entity_test_1->delete();
    // No entity using the bundle class has yet been deleted.
    $this->assertEquals(0, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postDeleteCount);
    $entity_test_2->delete();
    $this->assertEquals(0, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postDeleteCount);

    // Start deleting entities using the bundle class.
    $entity->delete();
    $this->assertEquals(1, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(1, EntityTestBundleClass::$postDeleteCount);
    $entity_2->delete();
    $this->assertEquals(2, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(2, EntityTestBundleClass::$postDeleteCount);

    // Verify that getEntityClass without bundle returns the default entity
    // class.
    $entity_class = $this->storage->getEntityClass(NULL);
    $this->assertEquals(EntityTest::class, $entity_class);

    // Verify that getEntityClass with a bundle returns the proper class.
    $entity_class = $this->storage->getEntityClass('bundle_class');
    $this->assertEquals(EntityTestBundleClass::class, $entity_class);

    // Verify that getEntityClass with a non-existing bundle returns the entity
    // class.
    $entity_class = $this->storage->getEntityClass('custom');
    $this->assertEquals(EntityTest::class, $entity_class);
  }

  /**
   * Tests making use of a custom bundle class for an entity without bundles.
   */
  public function testEntityNoBundleSubclass(): void {
    $this->container->get('state')->set('entity_test_bundle_class_enable_user_class', TRUE);
    $this->container->get('kernel')->rebuildContainer();
    $this->entityTypeManager->clearCachedDefinitions();
    $this->drupalSetUpCurrentUser();
    $entity = User::load(1);
    $this->assertInstanceOf(EntityTestUserClass::class, $entity);
  }

  /**
   * Checks exception is thrown if two bundles share the same bundle class.
   *
   * @legacy-covers Drupal\Core\Entity\ContentEntityStorageBase::create
   */
  public function testAmbiguousBundleClassExceptionCreate(): void {
    $this->container->get('state')->set('entity_test_bundle_class_enable_ambiguous_entity_types', TRUE);
    $this->entityTypeManager->clearCachedDefinitions();
    EntityTestHelper::createBundle('bundle_class');
    EntityTestHelper::createBundle('bundle_class_2');

    // Since we now have two bundles trying to reuse the same class, we expect
    // this to throw an exception.
    $this->expectException(AmbiguousBundleClassException::class);
    EntityTestBundleClass::create();
  }

  /**
   * Checks exception is thrown if two entity types share the same bundle class.
   *
   * @legacy-covers Drupal\Core\Entity\EntityTypeRepository::getEntityTypeFromClass
   */
  public function testAmbiguousBundleClassExceptionEntityTypeRepository(): void {
    $this->container->get('state')->set('entity_test_bundle_class_enable_ambiguous_entity_types', TRUE);
    EntityTestHelper::createBundle('entity_test_no_label');
    EntityTestHelper::createBundle('entity_test_no_label', NULL, 'entity_test_no_label');
    // Now that we have an entity bundle class that's shared by two entirely
    // different entity types, we expect an exception to be thrown.
    $this->expectException(AmbiguousBundleClassException::class);
    $this->container->get('entity_type.repository')->getEntityTypeFromClass(EntityTestAmbiguousBundleClass::class);
  }

  /**
   * Checks that no exception is thrown when two bundles share an entity class.
   *
   * @legacy-covers Drupal\Core\Entity\EntityTypeRepository::getEntityTypeFromClass
   */
  public function testNoAmbiguousBundleClassExceptionSharingEntityClass(): void {
    $shared_type_a = $this->container->get('entity_type.repository')->getEntityTypeFromClass(SharedEntityTestBundleClassA::class);
    $shared_type_b = $this->container->get('entity_type.repository')->getEntityTypeFromClass(SharedEntityTestBundleClassB::class);
    $this->assertSame('shared_type', $shared_type_a);
    $this->assertSame('shared_type', $shared_type_b);
  }

  /**
   * Checks exception thrown if a bundle class doesn't extend the entity class.
   */
  public function testBundleClassShouldExtendEntityClass(): void {
    $this->container->get('state')->set('entity_test_bundle_class_non_inheriting', TRUE);
    $this->entityTypeManager->clearCachedDefinitions();
    $this->expectException(BundleClassInheritanceException::class);
    EntityTestHelper::createBundle('bundle_class');
    $this->storage->create(['type' => 'bundle_class']);
  }

  /**
   * Checks exception thrown if a bundle class doesn't exist.
   */
  public function testBundleClassShouldExist(): void {
    $this->container->get('state')->set('entity_test_bundle_class_does_not_exist', TRUE);
    $this->entityTypeManager->clearCachedDefinitions();
    $this->expectException(MissingBundleClassException::class);
    EntityTestHelper::createBundle('bundle_class');
    $this->storage->create(['type' => 'bundle_class']);
  }

  /**
   * Tests that a module can override an entity-type class.
   *
   * Ensures a module can implement hook_entity_info_alter() and alter the
   * entity's class without needing to write to the last installed
   * definitions repository.
   */
  public function testEntityClassNotTakenFromActiveDefinitions(): void {
    $this->container->get('state')->set('entity_test_bundle_class_override_base_class', TRUE);
    $this->entityTypeManager->clearCachedDefinitions();
    $this->assertEquals(EntityTestVariant::class, $this->entityTypeManager->getStorage('entity_test')->getEntityClass());
  }

  /**
   * Tests bundle class discovery via attributes.
   */
  public function testBundleClassAttributeDiscovery(): void {
    $this->installEntitySchema('entity_test_with_bundle');
    // Confirm bundle entity type classes are not entity type definitions.
    \Drupal::service(ModuleInstallerInterface::class)->install(['entity_test_attribute_bundle_class']);
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    $this->assertArrayHasKey('entity_test', $definitions);
    $this->assertArrayNotHasKey('entity_test:bundle_class_a', $definitions);
    $this->assertArrayNotHasKey('entity_test:bundle_class_b', $definitions);
    $this->assertArrayNotHasKey('entity_test:subdir_bundle_class', $definitions);
    $this->assertArrayNotHasKey('entity_test_with_bundle:new_bundle', $definitions);
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test');
    $bundle_info_service = \Drupal::service(EntityTypeBundleInfoInterface::class);
    $bundle_info = $bundle_info_service->getAllBundleInfo();

    // Test bundle class info where all properties are overridden by the
    // attribute properties.
    $this->assertSame('Bundle class A label set by attribute', $bundle_info['entity_test']['bundle_class_a']['label']->getUntranslatedString());
    $this->assertFalse($bundle_info['entity_test']['bundle_class_a']['translatable']);
    $entity = $storage->create(['type' => 'bundle_class_a']);
    $this->assertInstanceOf(EntityTestBundleClassOverrideA::class, $entity);

    // Test bundle class info where all only the class is overridden by
    // attribute properties.
    $this->assertSame('Bundle class B', $bundle_info['entity_test']['bundle_class_b']['label']);
    $this->assertTrue($bundle_info['entity_test']['bundle_class_b']['translatable']);
    $entity = $storage->create(['type' => 'bundle_class_b']);
    $this->assertInstanceOf(EntityTestBundleClassOverrideB::class, $entity);

    // Confirm that attribute discovery of bundle classes works in
    // subdirectories of {module}/src/Entity. Attribute discovery of bundle
    // classes directly in the {module}/src/Entity directories are already
    // tested by other methods in this test class.
    $this->assertSame('subdir_bundle_class', $bundle_info['entity_test']['subdir_bundle_class']['label']);
    $this->assertArrayNotHasKey('translatable', $bundle_info['entity_test']['subdir_bundle_class']);
    $entity = $storage->create(['type' => 'subdir_bundle_class']);
    $this->assertInstanceOf(EntityTestSubdirBundleClass::class, $entity);

    // There have been no bundle entities created for entity_test_with_bundle,
    // so there should be no bundle class info for the entity type.
    $this->assertArrayNotHasKey('entity_test_with_bundle', $bundle_info);

    // Confirm bundle classes are not accessible as entity type definitions.
    $bundle_plugin_ids = [
      'entity_test:bundle_class_a',
      'entity_test:bundle_class_b',
      'entity_test:subdir_bundle_class',
      'entity_test_with_bundle:new_bundle',
    ];
    foreach ($bundle_plugin_ids as $id) {
      try {
        $throwable = NULL;
        $bundle_entity_type = \Drupal::entityTypeManager()->getDefinition($id);
        $this->assertNull($bundle_entity_type);
      }
      catch (\Throwable $throwable) {
      }
      $this->assertInstanceOf(\LogicException::class, $throwable);
      $this->assertSame('Bundle entity types are not supported directly.', $throwable->getMessage());
    }

    // Activate the entity_bundle_info_alter hook implementation in this class
    // to confirm the alter hook can change bundle info provided by attributes.
    $this->alterAttributeBundleInfo = TRUE;
    $bundle_info_service->clearCachedBundles();
    $bundle_info = $bundle_info_service->getAllBundleInfo();
    $this->assertSame('Overridden bundle class to SharedEntityTestBundleClassA', $bundle_info['entity_test']['subdir_bundle_class']['label']);
    $this->assertTrue($bundle_info['entity_test']['subdir_bundle_class']['translatable']);
    $entity = $storage->create(['type' => 'subdir_bundle_class']);
    $this->assertInstanceOf(SharedEntityTestBundleClassA::class, $entity);

    $this->assertSame('Overridden bundle class to SharedEntityTestBundleClassB', $bundle_info['entity_test']['bundle_class_b']['label']);
    $this->assertTrue($bundle_info['entity_test']['bundle_class_b']['translatable']);
    $entity = $storage->create(['type' => 'bundle_class_b']);
    $this->assertInstanceOf(SharedEntityTestBundleClassB::class, $entity);

    // Create the bundle entity for entity_test_with_bundle, and confirm the
    // bundle class defined by attributes is set.
    $entity_test_with_bundle_bundle_type = \Drupal::entityTypeManager()
      ->getDefinition('entity_test_with_bundle')
      ->getBundleEntityType();
    $bundle_entity = \Drupal::entityTypeManager()->getStorage($entity_test_with_bundle_bundle_type)
      ->create(['id' => 'new_bundle']);
    $bundle_entity->save();
    $bundle_info = $bundle_info_service->getAllBundleInfo();
    $this->assertSame('A new bundle for an entity type with a bundle entity type', $bundle_info['entity_test_with_bundle']['new_bundle']['label']->getUntranslatedString());
    $this->assertFalse($bundle_info['entity_test_with_bundle']['new_bundle']['translatable']);
    $entity = \Drupal::entityTypeManager()->getStorage('entity_test_with_bundle')
      ->create(['type' => 'new_bundle']);
    $this->assertInstanceOf(EntityTestWithBundleTypeNewBundle::class, $entity);
  }

  /**
   * Implements hook_entity_bundle_info_alter().
   */
  #[Hook('entity_bundle_info_alter')]
  public function entityBundleInfoAlter(array &$bundles): void {
    if (!$this->alterAttributeBundleInfo) {
      return;
    }

    // The alter hooks runs after the bundle info provided by attributes is
    // added, so override bundle information here so that it can be tested.
    if (isset($bundles['entity_test']['subdir_bundle_class'])) {
      $bundles['entity_test']['subdir_bundle_class']['class'] = SharedEntityTestBundleClassA::class;
      $bundles['entity_test']['subdir_bundle_class']['label'] = 'Overridden bundle class to SharedEntityTestBundleClassA';
      $bundles['entity_test']['subdir_bundle_class']['translatable'] = TRUE;
    }

    if (isset($bundles['entity_test']['bundle_class_b'])) {
      $bundles['entity_test']['bundle_class_b']['class'] = SharedEntityTestBundleClassB::class;
      $bundles['entity_test']['bundle_class_b']['label'] = 'Overridden bundle class to SharedEntityTestBundleClassB';
    }
  }

}
