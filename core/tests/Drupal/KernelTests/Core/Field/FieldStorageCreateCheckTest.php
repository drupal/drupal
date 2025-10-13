<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the field storage create check subscriber.
 */
#[Group('Field')]
#[RunTestsInSeparateProcesses]
class FieldStorageCreateCheckTest extends KernelTestBase {

  /**
   * Modules to load.
   *
   * @var array
   */
  protected static $modules = ['entity_test', 'field', 'user'];

  /**
   * Tests the field storage create check subscriber.
   */
  public function testFieldStorageCreateCheck(): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Creating the "entity_test.field_test" field storage definition without the entity schema "entity_test" being installed is not allowed.');

    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'integer',
    ])->save();
  }

}
