<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the deprecations of the original property.
 *
 * @group Entity
 * @group legacy
 */
class EntityOriginalDeprecationTest extends EntityKernelTestBase {

  /**
   * Tests deprecation of the original property.
   */
  public function testOriginalMagicGetSet(): void {
    $this->expectDeprecation('Setting the original property is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Entity\EntityInterface::setOriginal() instead. See https://www.drupal.org/node/3295826');
    $entity = EntityTest::create(['name' => 'original is deprecated']);
    $entity->original = clone $entity;

    $this->assertInstanceOf(EntityTest::class, $entity->getOriginal());

    $this->expectDeprecation('Getting the original property is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Entity\EntityInterface::getOriginal() instead. See https://www.drupal.org/node/3295826');
    $entity = EntityTest::create(['name' => 'original is deprecated']);
    $entity->setOriginal(clone $entity);

    $this->assertInstanceOf(EntityTest::class, $entity->original);

    $this->expectDeprecation('Checking for the original property is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Entity\EntityInterface::getOriginal() instead. See https://www.drupal.org/node/3295826');
    $entity = EntityTest::create(['name' => 'original is deprecated']);
    $this->assertFalse(isset($entity->original));

    $entity->setOriginal(clone $entity);
    $this->assertTrue(isset($entity->original));

    $this->expectDeprecation('Unsetting the original property is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Entity\EntityInterface::setOriginal() instead. See https://www.drupal.org/node/3295826');
    $entity = EntityTest::create(['name' => 'original is deprecated']);

    $entity->setOriginal(clone $entity);
    unset($entity->original);
    $this->assertNull($entity->getOriginal());
  }

}
