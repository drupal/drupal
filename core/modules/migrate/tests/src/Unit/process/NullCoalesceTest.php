<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\NullCoalesce;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the null_coalesce process plugin.
 */
#[CoversClass(NullCoalesce::class)]
#[Group('migrate')]
class NullCoalesceTest extends MigrateProcessTestCase {

  /**
   * Tests that an exception is thrown for a non-array value.
   *
   * @see ::transform
   */
  public function testExceptionOnInvalidValue(): void {
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
   * @throws \Drupal\migrate\MigrateException
   */
  #[DataProvider('transformDataProvider')]
  public function testTransform(array $source, bool|string|array|null $expected_result): void {
    $plugin = new NullCoalesce([], 'null_coalesce', []);
    $result = $plugin->transform($source, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides Data for ::testTransform.
   */
  public static function transformDataProvider(): array {
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
   * @throws \Drupal\migrate\MigrateException
   */
  #[DataProvider('transformWithDefaultProvider')]
  public function testTransformWithDefault(array $source, ?string $default_value, ?string $expected_result): void {
    $plugin = new NullCoalesce(['default_value' => $default_value], 'null_coalesce', []);
    $result = $plugin->transform($source, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides Data for ::testTransformWithDefault.
   */
  public static function transformWithDefaultProvider(): array {
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
