<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\Explode;
use Drupal\migrate\Plugin\migrate\process\Concat;

/**
 * Tests the Explode process plugin.
 *
 * @group migrate
 */
class ExplodeTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $configuration = [
      'delimiter' => ',',
    ];
    $this->plugin = new Explode($configuration, 'map', []);
    parent::setUp();
  }

  /**
   * Tests explode transform process works.
   */
  public function testTransform(): void {
    $value = $this->plugin->transform('foo,bar,tik', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(['foo', 'bar', 'tik'], $value);
  }

  /**
   * Tests explode transform process works with a limit.
   */
  public function testTransformLimit(): void {
    $plugin = new Explode(['delimiter' => '_', 'limit' => 2], 'map', []);
    $value = $plugin->transform('foo_bar_tik', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(['foo', 'bar_tik'], $value);
  }

  /**
   * Tests if the explode process can be chained with handles_multiple process.
   */
  public function testChainedTransform(): void {
    $exploded = $this->plugin->transform('One,Two,Three', $this->migrateExecutable, $this->row, 'destination_property');

    $concat = new Concat([], 'map', []);
    $concatenated = $concat->transform($exploded, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('OneTwoThree', $concatenated);
  }

  /**
   * Tests explode fails properly on non-strings.
   */
  public function testExplodeWithNonString(): void {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('is not a string');
    $this->plugin->transform(['foo'], $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests that explode works on non-strings but with strict set to FALSE.
   *
   * @dataProvider providerExplodeWithNonStrictAndEmptySource
   */
  public function testExplodeWithNonStrictAndEmptySource($value, $expected): void {
    $plugin = new Explode(['delimiter' => '|', 'strict' => FALSE], 'map', []);

    $processed = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected, $processed);
  }

  /**
   * Data provider for ::testExplodeWithNonStrictAndEmptySource().
   */
  public static function providerExplodeWithNonStrictAndEmptySource() {
    return [
      'normal_string' => ['a|b|c', ['a', 'b', 'c']],
      'integer_cast_to_string' => [123, ['123']],
      'zero_integer_cast_to_string' => [0, ['0']],
      'true_cast_to_string' => [TRUE, ['1']],
      'null_empty_array' => [NULL, []],
      'false_empty_array' => [FALSE, []],
      'empty_string_empty_array' => ['', []],
    ];
  }

  /**
   * Tests Explode exception handling when string-cast fails.
   */
  public function testExplodeWithNonStrictAndNonCastable(): void {
    $plugin = new Explode(['delimiter' => '|', 'strict' => FALSE], 'map', []);
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('cannot be casted to a string');
    $processed = $plugin->transform(['foo'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(['foo'], $processed);
  }

  /**
   * Tests Explode return values with an empty string and strict check.
   */
  public function testExplodeWithStrictAndEmptyString(): void {
    $plugin = new Explode(['delimiter' => '|'], 'map', []);
    $processed = $plugin->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame([''], $processed);
  }

  /**
   * Tests explode fails with empty delimiter.
   */
  public function testExplodeWithEmptyDelimiter(): void {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('delimiter is empty');
    $plugin = new Explode(['delimiter' => ''], 'map', []);
    $plugin->transform('foo,bar', $this->migrateExecutable, $this->row, 'destination_property');
  }

}
