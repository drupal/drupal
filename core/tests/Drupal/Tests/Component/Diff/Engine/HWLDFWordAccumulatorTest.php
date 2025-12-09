<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Diff\Engine;

use Drupal\Component\Diff\Engine\HWLDFWordAccumulator;
// cspell:ignore HWLDFWordAccumulator
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

// cspell:ignore wordword
/**
 * Test HWLDFWordAccumulator.
 */
#[CoversClass(HWLDFWordAccumulator::class)]
#[Group('Diff')]
class HWLDFWordAccumulatorTest extends TestCase {

  /**
   * Verify that we only get back a NBSP from an empty accumulator.
   *
   * @see Drupal\Component\Diff\Engine\HWLDFWordAccumulator::NBSP
   * @legacy-covers ::getLines
   */
  public function testGetLinesEmpty(): void {
    $acc = new HWLDFWordAccumulator();
    $this->assertEquals(['&#160;'], $acc->getLines());
  }

  /**
   * @return array
   *   - Expected array of lines from getLines().
   *   - Array of strings for the $words parameter to addWords().
   *   - String tag for the $tag parameter to addWords().
   */
  public static function provideAddWords(): array {
    return [
      [['wordword2'], ['word', 'word2'], 'tag'],
      [['word', 'word2'], ['word', "\nword2"], 'tag'],
      [['&#160;', 'word2'], ['', "\nword2"], 'tag'],
    ];
  }

  /**
   * @legacy-covers ::addWords
   */
  #[DataProvider('provideAddWords')]
  public function testAddWords($expected, $words, $tag): void {
    $acc = new HWLDFWordAccumulator();
    $acc->addWords($words, $tag);
    $this->assertEquals($expected, $acc->getLines());
  }

}
