<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Diff;

use Drupal\Component\Diff\Diff;
use Drupal\Component\Diff\DiffFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Test DiffFormatter classes.
 */
#[CoversClass(DiffFormatter::class)]
#[Group('Diff')]
class DiffFormatterTest extends TestCase {

  /**
   * @return array
   *   - Expected formatted diff output.
   *   - First array of text to diff.
   *   - Second array of text to diff.
   */
  public static function provideTestDiff(): array {
    return [
      'empty' => ['', [], []],
      'add' => [
        "3a3\n> line2a\n",
        ['line1', 'line2', 'line3'],
        ['line1', 'line2', 'line2a', 'line3'],
      ],
      'delete' => [
        "3d3\n< line2a\n",
        ['line1', 'line2', 'line2a', 'line3'],
        ['line1', 'line2', 'line3'],
      ],
      'change' => [
        "3c3\n< line2a\n---\n> line2b\n",
        ['line1', 'line2', 'line2a', 'line3'],
        ['line1', 'line2', 'line2b', 'line3'],
      ],
    ];
  }

  /**
   * Tests whether op classes returned by DiffEngine::diff() match expectations.
   *
   * @legacy-covers ::format
   */
  #[DataProvider('provideTestDiff')]
  public function testDiff($expected, $from, $to): void {
    $diff = new Diff($from, $to);
    $formatter = new DiffFormatter();
    $output = $formatter->format($diff);
    $this->assertEquals($expected, $output);
  }

}
