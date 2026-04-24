<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\KernelString;

use Drupal\Component\Uuid\Uuid;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the UUID field.
 */
#[Group('field')]
#[RunTestsInSeparateProcesses]
class UuidItemTest extends FieldKernelTestBase {

  /**
   * Tests 'uuid' random values.
   */
  public function testSampleValue(): void {
    $entity = EntityTest::create([]);
    $entity->save();

    $uuid_field = $entity->get('uuid');

    // Test the generateSampleValue() method.
    $uuid_field->generateSampleItems();
    $this->assertTrue(Uuid::isValid($uuid_field->value));
  }

  /**
   * Tests that UUID item values must be valid UUIDs.
   */
  public function testInvalidUuid(): void {
    $entity = EntityTest::create([
      'uuid' => 'not a valid uuid',
    ]);
    $violation = $entity->validate()->get(0);
    $this->assertSame('This is not a valid UUID.', (string) $violation->getMessage());
    $this->assertSame('uuid.0.value', $violation->getPropertyPath());
  }

}
