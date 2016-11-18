<?php

namespace Drupal\Tests\field\Kernel\String;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Component\Uuid\Uuid;

/**
 * Tests the UUID field.
 *
 * @group field
 */
class UuidItemTest extends FieldKernelTestBase {

  /**
   * Tests 'uuid' random values.
   */
  public function testSampleValue() {
    $entity = EntityTest::create([]);
    $entity->save();

    $uuid_field = $entity->get('uuid');

    // Test the generateSampleValue() method.
    $uuid_field->generateSampleItems();
    $this->assertTrue(Uuid::isValid($uuid_field->value));
  }

}
