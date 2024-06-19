<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Diff\Engine;

use Drupal\Component\Diff\Engine\DiffEngine;
use Drupal\Component\Diff\Engine\DiffOpAdd;
use Drupal\Component\Diff\Engine\DiffOpCopy;
use Drupal\Component\Diff\Engine\DiffOpChange;
use Drupal\Component\Diff\Engine\DiffOpDelete;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * Test DiffEngine class.
 *
 * @coversDefaultClass \Drupal\Component\Diff\Engine\DiffEngine
 *
 * @group Diff
 * @group legacy
 */
class DiffEngineTest extends TestCase {

  use ExpectDeprecationTrait;

  /**
   * @return array
   *   - Expected output in terms of return class. A list of class names
   *     expected to be returned by DiffEngine::diff().
   *   - An array of strings to change from.
   *   - An array of strings to change to.
   */
  public static function provideTestDiff() {
    return [
      'empty' => [[], [], []],
      'add' => [[DiffOpAdd::class], [], ['a']],
      'copy' => [[DiffOpCopy::class], ['a'], ['a']],
      'change' => [[DiffOpChange::class], ['a'], ['b']],
      'copy-and-change' => [
        [
          DiffOpCopy::class,
          DiffOpChange::class,
        ],
        ['a', 'b'],
        ['a', 'c'],
      ],
      'copy-change-copy' => [
        [
          DiffOpCopy::class,
          DiffOpChange::class,
          DiffOpCopy::class,
        ],
        ['a', 'b', 'd'],
        ['a', 'c', 'd'],
      ],
      'copy-change-copy-add' => [
        [
          DiffOpCopy::class,
          DiffOpChange::class,
          DiffOpCopy::class,
          DiffOpAdd::class,
        ],
        ['a', 'b', 'd'],
        ['a', 'c', 'd', 'e'],
      ],
      'copy-delete' => [
        [
          DiffOpCopy::class,
          DiffOpDelete::class,
        ],
        ['a', 'b', 'd'],
        ['a'],
      ],
      'change-copy' => [
        [
          DiffOpChange::class,
          DiffOpCopy::class,
        ],
        ['aa', 'bb', 'cc', 'd'],
        ['a', 'c', 'd'],
      ],
      'copy-change-copy-change' => [
        [
          DiffOpCopy::class,
          DiffOpChange::class,
          DiffOpCopy::class,
          DiffOpChange::class,
        ],
        ['a', 'bb', 'd', 'ee'],
        ['a', 'b', 'c', 'd', 'e'],
      ],
    ];
  }

  /**
   * Tests whether op classes returned by DiffEngine::diff() match expectations.
   *
   * @covers ::diff
   * @dataProvider provideTestDiff
   */
  public function testDiff($expected, $from, $to): void {
    $this->expectDeprecation('Drupal\Component\Diff\Engine\DiffEngine is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use sebastianbergmann/diff instead. See https://www.drupal.org/node/3337942');
    $diff_engine = new DiffEngine();
    $diff = $diff_engine->diff($from, $to);
    // Make sure we have the same number of results as expected.
    $this->assertSameSize($expected, $diff);
    // Make sure the diff objects match our expectations.
    foreach ($expected as $index => $op_class) {
      $this->assertEquals($op_class, get_class($diff[$index]));
    }
  }

  /**
   * Tests that two files can be successfully diffed.
   *
   * @covers ::diff
   */
  public function testDiffInfiniteLoop(): void {
    $this->expectDeprecation('Drupal\Component\Diff\Engine\DiffEngine is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use sebastianbergmann/diff instead. See https://www.drupal.org/node/3337942');
    $from = explode("\n", file_get_contents(__DIR__ . '/fixtures/file1.txt'));
    $to = explode("\n", file_get_contents(__DIR__ . '/fixtures/file2.txt'));
    $diff_engine = new DiffEngine();
    $diff = $diff_engine->diff($from, $to);
    $this->assertCount(4, $diff);
    $this->assertEquals($diff[0], new DiffOpDelete(['    - image.style.max_650x650']));
    $this->assertEquals($diff[1], new DiffOpCopy(['    - image.style.max_325x325']));
    $this->assertEquals($diff[2], new DiffOpAdd(['    - image.style.max_650x650', '_core:', '  default_config_hash: random_hash_string_here']));
    $this->assertEquals($diff[3], new DiffOpCopy(['fallback_image_style: max_325x325', '']));
  }

}
