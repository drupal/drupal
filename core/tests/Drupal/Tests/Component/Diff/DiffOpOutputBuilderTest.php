<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Diff;

use Drupal\Component\Diff\DiffOpOutputBuilder;
use Drupal\Component\Diff\Engine\DiffOpAdd;
use Drupal\Component\Diff\Engine\DiffOpCopy;
use Drupal\Component\Diff\Engine\DiffOpChange;
use Drupal\Component\Diff\Engine\DiffOpDelete;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;

/**
 * @coversDefaultClass \Drupal\Component\Diff\DiffOpOutputBuilder
 *
 * @group Diff
 */
class DiffOpOutputBuilderTest extends TestCase {

  /**
   * @return array
   *   - Expected output in terms of return class. A list of class names
   *     expected to be returned by DiffEngine::diff().
   *   - An array of strings to change from.
   *   - An array of strings to change to.
   */
  public function provideTestDiff(): array {
    return [
      'empty' => [[], [], []],
      'add' => [[new DiffOpAdd(['a'])], [], ['a']],
      'copy' => [[new DiffOpCopy(['a'])], ['a'], ['a']],
      'change' => [[new DiffOpChange(['a'], ['b'])], ['a'], ['b']],
      'copy-and-change' => [
        [
          new DiffOpCopy(['a']),
          new DiffOpChange(['b'], ['c']),
        ],
        ['a', 'b'],
        ['a', 'c'],
      ],
      'copy-change-copy' => [
        [
          new DiffOpCopy(['a']),
          new DiffOpChange(['b'], ['c']),
          new DiffOpCopy(['d']),
        ],
        ['a', 'b', 'd'],
        ['a', 'c', 'd'],
      ],
      'copy-change-copy-add' => [
        [
          new DiffOpCopy(['a']),
          new DiffOpChange(['b'], ['c']),
          new DiffOpCopy(['d']),
          new DiffOpAdd(['e']),
        ],
        ['a', 'b', 'd'],
        ['a', 'c', 'd', 'e'],
      ],
      'copy-delete' => [
        [
          new DiffOpCopy(['a']),
          new DiffOpDelete(['b', 'd']),
        ],
        ['a', 'b', 'd'],
        ['a'],
      ],
      'change-copy' => [
        [
          new DiffOpChange(['aa', 'bb', 'cc'], ['a', 'c']),
          new DiffOpCopy(['d']),
        ],
        ['aa', 'bb', 'cc', 'd'],
        ['a', 'c', 'd'],
      ],
      'copy-change-copy-change' => [
        [
          new DiffOpCopy(['a']),
          new DiffOpChange(['bb'], ['b', 'c']),
          new DiffOpCopy(['d']),
          new DiffOpChange(['ee'], ['e']),
        ],
        ['a', 'bb', 'd', 'ee'],
        ['a', 'b', 'c', 'd', 'e'],
      ],
    ];
  }

  /**
   * Tests whether op classes returned match expectations.
   *
   * @covers ::toOpsArray
   * @dataProvider provideTestDiff
   */
  public function testToOpsArray(array $expected, array $from, array $to): void {
    $diffOpBuilder = new DiffOpOutputBuilder();
    $differ = new Differ($diffOpBuilder);
    $diff = $differ->diffToArray($from, $to);
    $this->assertEquals($expected, $diffOpBuilder->toOpsArray($diff));
  }

  /**
   * @covers ::getDiff
   * @dataProvider provideTestDiff
   */
  public function testGetDiff(array $expected, array $from, array $to): void {
    $differ = new Differ(new DiffOpOutputBuilder());
    $diff = $differ->diff($from, $to);
    $this->assertEquals($expected, unserialize($diff));
  }

  /**
   * Tests that two files can be successfully diffed.
   *
   * @covers ::toOpsArray
   */
  public function testDiffInfiniteLoop(): void {
    $from = explode("\n", file_get_contents(__DIR__ . '/Engine/fixtures/file1.txt'));
    $to = explode("\n", file_get_contents(__DIR__ . '/Engine/fixtures/file2.txt'));
    $diffOpBuilder = new DiffOpOutputBuilder();
    $differ = new Differ($diffOpBuilder);
    $diff = $differ->diffToArray($from, $to);
    $diffOps = $diffOpBuilder->toOpsArray($diff);
    $this->assertCount(4, $diffOps);
    $this->assertEquals($diffOps[0], new DiffOpAdd(['    - image.style.max_325x325']));
    $this->assertEquals($diffOps[1], new DiffOpCopy(['    - image.style.max_650x650']));
    $this->assertEquals($diffOps[2], new DiffOpChange(['    - image.style.max_325x325'], ['_core:', '  default_config_hash: random_hash_string_here']));
    $this->assertEquals($diffOps[3], new DiffOpCopy(['fallback_image_style: max_325x325', '']));
  }

}
