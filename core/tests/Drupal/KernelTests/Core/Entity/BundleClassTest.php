<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Exception\AmbiguousBundleClassException;
use Drupal\Core\Entity\Exception\BundleClassInheritanceException;
use Drupal\Core\Entity\Exception\MissingBundleClassException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test_bundle_class\Entity\EntityTestAmbiguousBundleClass;
use Drupal\entity_test_bundle_class\Entity\EntityTestBundleClass;
use Drupal\entity_test_bundle_class\Entity\EntityTestUserClass;
use Drupal\entity_test_bundle_class\Entity\EntityTestVariant;
use Drupal\user\Entity\User;

/**
 * Tests entity bundle classes.
 *
 * @group Entity
 */
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->storage = $this->entityTypeManager->getStorage('entity_test');
  }

  /**
   * Tests making use of a custom bundle class.
   */
  public function testEntitySubclass() {
    entity_test_create_bundle('bundle_class');

    // Ensure we start life with empty counters.
    $this->assertEquals(0, EntityTestBundleClass::$preCreateCount);
    $this->assertEquals(0, EntityTestBundleClass::$preDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postDeleteCount);
    $this->assertEquals(0, EntityTestBundleClass::$postLoadCount);
    $this->assertCount(0, EntityTestBundleClass::$postLoadEntitiesCount);

    // Verify statically created entity with bundle class returns correct class.
    $entity = EntityTestBundleClass::create();
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);

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
    entity_test_create_bundle('entity_test');

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
    $entities = $this->storage->loadMultiple($entity_ids);
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

    $entities = $this->storage->loadMultiple($entity_ids);
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
  public function testEntityNoBundleSubclass() {
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
   * @covers Drupal\Core\Entity\ContentEntityStorageBase::create
   */
  public function testAmbiguousBundleClassExceptionCreate() {
    $this->container->get('state')->set('entity_test_bundle_class_enable_ambiguous_entity_types', TRUE);
    $this->entityTypeManager->clearCachedDefinitions();
    entity_test_create_bundle('bundle_class');
    entity_test_create_bundle('bundle_class_2');

    // Since we now have two bundles trying to reuse the same class, we expect
    // this to throw an exception.
    $this->expectException(AmbiguousBundleClassException::class);
    EntityTestBundleClass::create();
  }

  /**
   * Checks exception is thrown if two entity types share the same bundle class.
   *
   * @covers Drupal\Core\Entity\EntityTypeRepository::getEntityTypeFromClass
   */
  public function testAmbiguousBundleClassExceptionEntityTypeRepository() {
    $this->container->get('state')->set('entity_test_bundle_class_enable_ambiguous_entity_types', TRUE);
    entity_test_create_bundle('entity_test_no_label');
    entity_test_create_bundle('entity_test_no_label', NULL, 'entity_test_no_label');
    // Now that we have an entity bundle class that's shared by two entirely
    // different entity types, we expect an exception to be thrown.
    $this->expectException(AmbiguousBundleClassException::class);
    $entity_type = $this->container->get('entity_type.repository')->getEntityTypeFromClass(EntityTestAmbiguousBundleClass::class);
  }

  /**
   * Checks exception thrown if a bundle class doesn't extend the entity class.
   */
  public function testBundleClassShouldExtendEntityClass() {
    $this->container->get('state')->set('entity_test_bundle_class_non_inheriting', TRUE);
    $this->entityTypeManager->clearCachedDefinitions();
    $this->expectException(BundleClassInheritanceException::class);
    entity_test_create_bundle('bundle_class');
    $this->storage->create(['type' => 'bundle_class']);
  }

  /**
   * Checks exception thrown if a bundle class doesn't exist.
   */
  public function testBundleClassShouldExist() {
    $this->container->get('state')->set('entity_test_bundle_class_does_not_exist', TRUE);
    $this->entityTypeManager->clearCachedDefinitions();
    $this->expectException(MissingBundleClassException::class);
    entity_test_create_bundle('bundle_class');
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

}
