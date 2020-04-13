<?php

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
   * Test explode transform process works.
   */
  public function testTransform() {
    $value = $this->plugin->transform('foo,bar,tik', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame(['foo', 'bar', 'tik'], $value);
  }

  /**
   * Test explode transform process works with a limit.
   */
  public function testTransformLimit() {
    $plugin = new Explode(['delimiter' => '_', 'limit' => 2], 'map', []);
    $value = $plugin->transform('foo_bar_tik', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame(['foo', 'bar_tik'], $value);
  }

  /**
   * Test if the explode process can be chained with a handles_multiple process.
   */
  public function testChainedTransform() {
    $exploded = $this->plugin->transform('foo,bar,tik', $this->migrateExecutable, $this->row, 'destinationproperty');

    $concat = new Concat([], 'map', []);
    $concatenated = $concat->transform($exploded, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('foobartik', $concatenated);
  }

  /**
   * Test explode fails properly on non-strings.
   */
  public function testExplodeWithNonString() {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('is not a string');
    $this->plugin->transform(['foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests that explode works on non-strings but with strict set to FALSE.
   *
   * @dataProvider providerExplodeWithNonStrictAndEmptySource
   */
  public function testExplodeWithNonStrictAndEmptySource($value, $expected) {
    $plugin = new Explode(['delimiter' => '|', 'strict' => FALSE], 'map', []);

    $processed = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($expected, $processed);
  }

  /**
   * Data provider for ::testExplodeWithNonStrictAndEmptySource().
   */
  public function providerExplodeWithNonStrictAndEmptySource() {
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
   * Tests that explode raises an exception when the value cannot be casted to
   * string.
   */
  public function testExplodeWithNonStrictAndNonCastable() {
    $plugin = new Explode(['delimiter' => '|', 'strict' => FALSE], 'map', []);
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('cannot be casted to a string');
    $processed = $plugin->transform(['foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame(['foo'], $processed);
  }

  /**
   * Tests that explode with an empty string and strict check returns a
   * non-empty array.
   */
  public function testExplodeWithStrictAndEmptyString() {
    $plugin = new Explode(['delimiter' => '|'], 'map', []);
    $processed = $plugin->transform('', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame([''], $processed);
  }

  /**
   * Test explode fails with empty delimiter.
   */
  public function testExplodeWithEmptyDelimiter() {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('delimiter is empty');
    $plugin = new Explode(['delimiter' => ''], 'map', []);
    $plugin->transform('foo,bar', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
