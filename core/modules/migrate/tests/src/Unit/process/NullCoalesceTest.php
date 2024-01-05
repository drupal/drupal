<?php

declare(strict_types=1);

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
    (new NullCoalesce([], 'null_coalesce', []))->transform('invalid', $this->migrateExecutable, $this->row, 'destination_property');
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
    $result = $plugin->transform($source, $this->migrateExecutable, $this->row, 'destination_property');
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
   * Tests null_coalesce.
   *
   * @param array $source
   *   The source value.
   * @param string $default_value
   *   The default value.
   * @param mixed $expected_result
   *   The expected result.
   *
   * @covers ::transform
   *
   * @dataProvider transformWithDefaultProvider
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function testTransformWithDefault(array $source, $default_value, $expected_result) {
    $plugin = new NullCoalesce(['default_value' => $default_value], 'null_coalesce', []);
    $result = $plugin->transform($source, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides Data for ::testTransformWithDefault.
   */
  public function transformWithDefaultProvider() {
    return [
      'default not used' => [
        'source' => [NULL, NULL, 'Test', 'Test 2'],
        'default_value' => 'default',
        'expected_result' => 'Test',
      ],
      'default string' => [
        'source' => [NULL, NULL],
        'default_value' => 'default',
        'expected_result' => 'default',
      ],
      'default NULL' => [
        'source' => [NULL, NULL],
        'default_value' => NULL,
        'expected_result' => NULL,
      ],
    ];
  }

}
