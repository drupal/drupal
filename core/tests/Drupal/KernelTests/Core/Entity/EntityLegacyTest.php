<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
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
  }

  /**
   * @expectedDeprecation entity_load_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity type storage's loadMultiple() method. See https://www.drupal.org/node/2266845
   * @expectedDeprecation entity_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use the entity type storage's load() method. See https://www.drupal.org/node/2266845
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
  }

}
