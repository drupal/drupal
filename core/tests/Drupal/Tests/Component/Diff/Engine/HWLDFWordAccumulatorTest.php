<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Diff\Engine\DiffOpTest.
 */

namespace Drupal\Tests\Component\Diff\Engine;

use Drupal\Component\Diff\Engine\HWLDFWordAccumulator;

/**
 * Test HWLDFWordAccumulator.
 *
 * @coversDefaultClass \Drupal\Component\Diff\Engine\HWLDFWordAccumulator
 *
 * @group Diff
 */
class HWLDFWordAccumulatorTest extends \PHPUnit_Framework_TestCase {

  /**
   * Verify that we only get back a NBSP from an empty accumulator.
   *
   * @covers ::getLines
   *
   * @see Drupal\Component\Diff\Engine\HWLDFWordAccumulator::NBSP
   */
  public function testGetLinesEmpty() {
    $acc = new HWLDFWordAccumulator();
    $this->assertEquals(['&#160;'], $acc->getLines());
  }

  /**
   * @return array
   *   - Expected array of lines from getLines().
   *   - Array of strings for the $words parameter to addWords().
   *   - String tag for the $tag parameter to addWords().
   */
  public function provideAddWords() {
    return [
      [['wordword2'], ['word', 'word2'], 'tag'],
      [['word', 'word2'], ['word', "\nword2"], 'tag'],
      [['&#160;', 'word2'], ['', "\nword2"], 'tag'],
    ];
  }

  /**
   * @covers ::addWords
   * @dataProvider provideAddWords
   */
  public function testAddWords($expected, $words, $tag) {
    $acc = new HWLDFWordAccumulator();
    $acc->addWords($words, $tag);
    $this->assertEquals($expected, $acc->getLines());
  }

}
