<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\NullCoalesce;

/**
 * Tests the null_coalesce process plugin.
 *
 * @group migrate
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\NullCoalesce
 */
class NullCoalesceTest extends MigrateProcessTestCase {

  /**
   * Tests that an exception is thrown for a non-array value.
   *
   * @covers ::transform
   */
  public function testExceptionOnInvalidValue() {
    $this->expectException(MigrateException::class);
    (new NullCoalesce([], 'null_coalesce', []))->transform('invalid', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests null_coalesce.
   *
   * @param array $source
   *   The source value.
   * @param mixed $expected_result
   *   The expected result.
   *
   * @covers ::transform
   *
   * @dataProvider transformDataProvider
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function testTransform(array $source, $expected_result) {
    $plugin = new NullCoalesce([], 'null_coalesce', []);
    $result = $plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides Data for ::testTransform.
   */
  public function transformDataProvider() {
    return [
      'all null' => [
        'source' => [NULL, NULL, NULL],
        'expected_result' => NULL,
      ],
      'false first' => [
        'source' => [FALSE, NULL, NULL],
        'expected_result' => FALSE,
      ],
      'no null' => [
        'source' => ['test', 'test2'],
        'expected_result' => 'test',
      ],
      'string first' => [
        'source' => ['test', NULL, 'test2'],
        'expected_result' => 'test',
      ],
      'empty string' => [
        'source' => [NULL, '', NULL],
        'expected_result' => '',
      ],
      'array' => [
        'source' => [NULL, NULL, [1, 2, 3]],
        'expected_result' => [1, 2, 3],
      ],
    ];
  }

  /**
   * Tests null_coalesce with default value.
   *
   * @covers ::transform
   */
  public function testTransformWithDefault() {
    $plugin = new NullCoalesce(['default_value' => 'default'], 'null_coalesce', []);
    $result = $plugin->transform([NULL, NULL, 'Test', 'Test 2'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('Test', $result);

    $this->assertSame('default', $plugin->transform([NULL, NULL], $this->migrateExecutable, $this->row, 'destinationproperty'));
  }

}
