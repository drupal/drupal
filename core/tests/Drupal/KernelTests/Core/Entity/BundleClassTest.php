<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Exception\AmbiguousBundleClassException;
use Drupal\Core\Entity\Exception\BundleClassInheritanceException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test_bundle_class\Entity\EntityTestBundleClass;

/**
 * Tests entity bundle classes.
 *
 * @group Entity
 */
class BundleClassTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test_bundle_class'];

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
   * Tests making use of a custom bundle field.
   */
  public function testEntitySubclass() {
    entity_test_create_bundle('bundle_class');

    // Verify statically created entity with bundle class returns correct class.
    $entity = EntityTestBundleClass::create();
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);

    // Verify statically created entity with bundle class returns correct
    // bundle.
    $entity = EntityTestBundleClass::create(['type' => 'custom']);
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);
    $this->assertEquals('bundle_class', $entity->bundle());

    // Verify that the entity storage creates the entity using the proper class.
    $entity = $this->storage->create(['type' => 'bundle_class']);
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);

    // Verify that loading an entity returns the proper class.
    $entity->save();
    $id = $entity->id();
    $this->storage->resetCache();
    $entity = $this->storage->load($id);
    $this->assertInstanceOf(EntityTestBundleClass::class, $entity);

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
   * Checks exception is thrown if multiple classes implement the same bundle.
   */
  public function testAmbiguousBundleClassException() {
    $this->container->get('state')->set('entity_test_bundle_class_enable_ambiguous_entity_types', TRUE);
    $this->entityTypeManager->clearCachedDefinitions();
    $this->expectException(AmbiguousBundleClassException::class);
    entity_test_create_bundle('bundle_class');

    // Since we now have two entity types that returns the same class for the
    // same bundle, we expect this to throw an exception.
    EntityTestBundleClass::create();
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

}
