<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d6;

use Drupal\field\Plugin\migrate\process\d6\FieldTypeDefaults;
use Drupal\migrate\MigrateException;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests D6 fields defaults.
 *
 * @coversDefaultClass \Drupal\field\Plugin\migrate\process\d6\FieldTypeDefaults
 * @group field
 */
class FieldTypeDefaultsTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->plugin = new FieldTypeDefaults([], 'd6_field_type_defaults', []);
  }

  /**
   * Tests various default cases.
   *
   * @covers ::transform
   */
  public function testDefaults(): void {
    $this->row->expects($this->once())
      ->method('getSourceProperty')
      ->willReturn('date');

    // Assert common values are passed through without modification.
    $this->assertNull($this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'property'));
    $this->assertEquals('string', $this->plugin->transform('string', $this->migrateExecutable, $this->row, 'property'));
    $this->assertEquals(1234, $this->plugin->transform(1234, $this->migrateExecutable, $this->row, 'property'));
    // Assert that an array checks that this is a date field(above mock assert)
    // and returns "datetime_default".
    $this->assertEquals('datetime_default', $this->plugin->transform([], $this->migrateExecutable, $this->row, 'property'));
  }

  /**
   * Tests an exception is thrown when the input is not a date field.
   *
   * @covers ::transform
   */
  public function testDefaultsException(): void {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage(sprintf('Failed to lookup field type %s in the static map.', var_export([], TRUE)));
    $this->plugin->transform([], $this->migrateExecutable, $this->row, 'property');
  }

}
