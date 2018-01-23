<?php

namespace Drupal\KernelTests\Core\Datetime;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests timestamp schema.
 *
 * @group Common
 */
class TimestampSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field', 'field_timestamp_test'];

  /**
   * Tests if the timestamp field schema is validated.
   */
  public function testTimestampSchema() {
    $this->installConfig(['field_timestamp_test']);
    // Make at least an assertion.
    $this->assertTrue(TRUE);
  }

}
