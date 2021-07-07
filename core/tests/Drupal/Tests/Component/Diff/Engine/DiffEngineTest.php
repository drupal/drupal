<?php

namespace Drupal\Tests\Component\Diff\Engine;

use Drupal\Component\Diff\Engine\DiffEngine;
use Drupal\Component\Diff\Engine\DiffOpAdd;
use Drupal\Component\Diff\Engine\DiffOpCopy;
use Drupal\Component\Diff\Engine\DiffOpChange;
use Drupal\Component\Diff\Engine\DiffOpDelete;
use PHPUnit\Framework\TestCase;

/**
 * Test DiffEngine class.
 *
 * @coversDefaultClass \Drupal\Component\Diff\Engine\DiffEngine
 *
 * @group Diff
 */
class DiffEngineTest extends TestCase {

  /**
   * @return array
   *   - Expected output in terms of return class. A list of class names
   *     expected to be returned by DiffEngine::diff().
   *   - An array of strings to change from.
   *   - An array of strings to change to.
   */
  public function provideTestDiff() {
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
    ];
  }

  /**
   * Tests whether op classes returned by DiffEngine::diff() match expectations.
   *
   * @covers ::diff
   * @dataProvider provideTestDiff
   */
  public function testDiff($expected, $from, $to) {
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
  public function testDiffInfiniteLoop() {
    $from = explode("\n", file_get_contents(__DIR__ . '/fixtures/file1.txt'));
    $to = explode("\n", file_get_contents(__DIR__ . '/fixtures/file2.txt'));
    $diff_engine = new DiffEngine();
    $diff = $diff_engine->diff($from, $to);
    $this->assertCount(4, $diff);
    $this->assertEquals($diff[0], new DiffOpDelete(['    - image.style.max_650x650']));
    $this->assertEquals($diff[1], new DiffOpCopy(['    - image.style.max_325x325']));
    $this->assertEquals($diff[2], new DiffOpAdd(['    - image.style.max_650x650', '_core:', '  default_config_hash: 3mjM9p-kQ8syzH7N8T0L9OnCJDSPvHAZoi3q6jcXJKM']));
    $this->assertEquals($diff[3], new DiffOpCopy(['fallback_image_style: max_325x325', '']));
  }

}
