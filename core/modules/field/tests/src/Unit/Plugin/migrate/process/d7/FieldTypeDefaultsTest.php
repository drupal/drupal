<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d7;

use Drupal\field\Plugin\migrate\process\d7\FieldTypeDefaults;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests D7 field formatter defaults.
 *
 * @coversDefaultClass \Drupal\field\Plugin\migrate\process\d7\FieldTypeDefaults
 * @group field
 */
class FieldTypeDefaultsTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->plugin = new FieldTypeDefaults([], 'd7_field_type_defaults', []);
  }

  /**
   * Tests various default cases.
   *
   * @covers ::transform
   */
  public function testDefaults() {
    // Assert common values are passed through without modification.
    $this->assertNull($this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'property'));
    $this->assertEquals('string', $this->plugin->transform('string', $this->migrateExecutable, $this->row, 'property'));
    $this->assertEquals(1234, $this->plugin->transform(1234, $this->migrateExecutable, $this->row, 'property'));
    // Assert that an array will return the second item, which is the source
    // formatter type.
    $this->assertEquals('datetime_default', $this->plugin->transform(['datetime', 'datetime_default'], $this->migrateExecutable, $this->row, 'property'));
  }

}
