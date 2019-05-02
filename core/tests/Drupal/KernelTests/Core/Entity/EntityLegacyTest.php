<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests legacy entity functions.
 *
 * @group entity
 * @group legacy
 */
class EntityLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_rev');
  }

  /**
   * Tests that entity_delete_multiple triggers an error.
   *
   * @expectedDeprecation entity_delete_multiple is deprecated in drupal:8.0.0 and will be removed in drupal:9.0.0. Use the entity storage's delete() method to delete multiple entities. @see https://www.drupal.org/node/3051072
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEntityDeleteMultiple() {
    EntityTest::create(['name' => 'published entity'])->save();
    EntityTest::create(['name' => 'published entity'])->save();
    $this->assertCount(2, \Drupal::entityTypeManager()->getStorage('entity_test')->loadMultiple());
    entity_delete_multiple('entity_test', [1, 2]);
    $this->assertCount(0, \Drupal::entityTypeManager()->getStorage('entity_test')->loadMultiple());
  }

  /**
   * @expectedDeprecation entity_load_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity type storage's loadMultiple() method. See https://www.drupal.org/node/2266845
   * @expectedDeprecation entity_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity type storage's load() method. See https://www.drupal.org/node/2266845
   * @expectedDeprecation entity_get_bundles() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getBundleInfo() for a single bundle, or \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getAllBundleInfo() for all bundles. See https://www.drupal.org/node/3051077
   * @expectedDeprecation entity_page_label() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity's label() method. See https://www.drupal.org/node/2549923
   * @expectedDeprecation entity_revision_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity type storage's loadRevision() method. See https://www.drupal.org/node/1818376
   * @expectedDeprecation entity_revision_delete() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity type storage's deleteRevision() method. See https://www.drupal.org/node/1818376
   * @expectedDeprecation entity_load_unchanged() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity type storage's loadUnchanged() method. See https://www.drupal.org/node/1935744
   */
  public function testEntityLegacyCode() {
    $this->assertCount(0, entity_load_multiple('entity_test'));
    $this->assertCount(0, entity_load_multiple('entity_test_mul'));

    EntityTest::create(['name' => 'published entity'])->save();
    $this->assertCount(1, entity_load_multiple('entity_test'));
    $this->assertCount(0, entity_load_multiple('entity_test_mul'));

    EntityTest::create(['name' => 'published entity'])->save();
    EntityTestMul::create(['name' => 'published entity'])->save();
    $this->assertCount(2, entity_load_multiple('entity_test'));
    $this->assertCount(1, entity_load_multiple('entity_test_mul'));

    $this->assertNull(entity_load('entity_test', 100));
    $this->assertInstanceOf(EntityInterface::class, entity_load('entity_test', 1));

    $this->assertEquals(['entity_test' => ['label' => 'Entity Test Bundle']], entity_get_bundles('entity_test'));
    $this->assertEquals(['entity_test' => ['label' => 'Entity Test Bundle']], entity_get_bundles()['entity_test']);

    $entity = EntityTestRev::create(['name' => 'revision test']);
    $entity->save();
    $this->assertEquals('revision test', entity_page_label($entity));
    $first_revision_id = $entity->getRevisionId();
    $entity->setNewRevision(TRUE);
    $entity->save();
    $first_revision = entity_revision_load($entity->getEntityTypeId(), $first_revision_id);
    $this->assertEquals($first_revision_id, $first_revision->getRevisionId());
    entity_revision_delete($entity->getEntityTypeId(), $first_revision_id);
    $this->assertNull(entity_revision_load($entity->getEntityTypeId(), $first_revision_id));

    $entity->setName('Different name');
    $entity = entity_load_unchanged($entity->getEntityTypeId(), $entity->id());
    $this->assertEquals('revision test', $entity->label());
  }

  /**
   * @expectedDeprecation entity_get_display() is deprecated in drupal:8.8.0. It will be removed before drupal:9.0.0. Use \Drupal::service('entity_display.repository')->getViewDisplay() instead. See https://www.drupal.org/node/2835616
   * @expectedDeprecation entity_get_form_display() is deprecated in drupal:8.8.0. It will be removed before drupal:9.0.0. Use \Drupal::service('entity_display.repository')->getFormDisplay() instead. See https://www.drupal.org/node/2835616
   */
  public function testLegacyDisplayFunctions() {
    $view_display = entity_get_display('entity_test', 'entity_test', 'default');
    $this->assertInstanceOf(EntityViewDisplayInterface::class, $view_display);
    $this->assertEquals('entity_test.entity_test.default', $view_display->id());
    $form_display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $form_display);
    $this->assertEquals('entity_test.entity_test.default', $form_display->id());
  }

  /**
   * @expectedDeprecation entity_load_multiple_by_properties() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity type storage's loadByProperties() method. See https://www.drupal.org/node/3050910
   */
  public function testEntityLoadMultipleByProperties() {
    $this->assertCount(0, entity_load_multiple_by_properties('entity_test', ['name' => 'published entity']));

    EntityTest::create(['name' => 'published entity'])->save();
    $this->assertCount(1, entity_load_multiple_by_properties('entity_test', ['name' => 'published entity']));
    $this->assertCount(0, entity_load_multiple_by_properties('entity_test_mul', ['name' => 'published entity']));

    EntityTest::create(['name' => 'published entity'])->save();
    EntityTestMul::create(['name' => 'published entity'])->save();
    $this->assertCount(2, entity_load_multiple_by_properties('entity_test', ['name' => 'published entity']));
    $this->assertCount(1, entity_load_multiple_by_properties('entity_test_mul', ['name' => 'published entity']));
  }

  /**
   * @expectedDeprecation entity_view() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode, $langcode) instead. See https://www.drupal.org/node/3033656
   * @expectedDeprecation entity_view_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->viewMultiple($entities, $view_mode, $langcode) instead. See https://www.drupal.org/node/3033656
   */
  public function testEntityView() {
    $entity = EntityTest::create();
    $this->assertNotEmpty(entity_view($entity, 'default'));
    $entities = [
      EntityTest::create(),
      EntityTest::create(),
    ];
    $this->assertEquals(4, count(entity_view_multiple($entities, 'default')));
  }

  /**
   * Tests deprecation of the entity_create() function.
   *
   * @expectedDeprecation entity_create() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the create() method of the entity type class directly or \Drupal::entityTypeManager()->getStorage($entity_type)->create($values) instead. See https://www.drupal.org/node/2266845
   */
  public function testEntityCreate() {
    $values = ['name' => $this->getRandomGenerator()->sentences(2)];
    $expected = EntityTest::create($values);
    $actual = entity_create('entity_test', $values);
    $this->assertEquals($expected->label(), $actual->label());
    $this->assertEquals($expected->getEntityTypeId(), $actual->getEntityTypeId());
    $this->assertInstanceOf(EntityTest::class, $actual);
  }

}
