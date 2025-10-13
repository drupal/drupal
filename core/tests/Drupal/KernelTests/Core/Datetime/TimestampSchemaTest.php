<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Datetime;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests timestamp schema.
 */
#[Group('Common')]
#[RunTestsInSeparateProcesses]
class TimestampSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'field_timestamp_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests if the timestamp field schema is validated.
   */
  public function testTimestampSchema(): void {
    $this->installConfig(['field_timestamp_test']);
    // Make at least an assertion.
    $this->assertTrue(TRUE);
  }

}
