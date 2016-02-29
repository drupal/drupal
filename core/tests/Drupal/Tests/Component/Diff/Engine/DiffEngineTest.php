<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Diff\Engine\DiffEngineTest.
 */

namespace Drupal\Tests\Component\Diff\Engine;

use Drupal\Component\Diff\Engine\DiffEngine;
use Drupal\Component\Diff\Engine\DiffOpAdd;
use Drupal\Component\Diff\Engine\DiffOpCopy;
use Drupal\Component\Diff\Engine\DiffOpChange;
use Drupal\Component\Diff\Engine\DiffOpDelete;

/**
 * Test DiffEngine class.
 *
 * @coversDefaultClass \Drupal\Component\Diff\Engine\DiffEngine
 *
 * @group Diff
 */
class DiffEngineTest extends \PHPUnit_Framework_TestCase {

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
    $this->assertCount(count($expected), $diff);
    // Make sure the diff objects match our expectations.
    foreach ($expected as $index => $op_class) {
      $this->assertEquals($op_class, get_class($diff[$index]));
    }
  }

}
