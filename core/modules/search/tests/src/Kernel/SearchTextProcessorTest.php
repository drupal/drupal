<?php

namespace Drupal\Tests\search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search\SearchTextProcessorInterface;

/**
 * Test search text preprocessing functionality.
 *
 * @group search
 */
class SearchTextProcessorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search'];

  /**
   * Tests that text processing handles Unicode characters correctly.
   */
  public function testSearchTextProcessorUnicode() {
    // This test uses a file that was constructed so that the even lines are
    // boundary characters, and the odd lines are valid word characters. (It
    // was generated as a sequence of all the Unicode characters, and then the
    // boundary characters (punctuation, spaces, etc.) were split off into
    // their own lines). So the even-numbered lines should simplify to nothing,
    // and the odd-numbered lines we need to split into shorter chunks and
    // verify that text processing doesn't lose any characters.
    $input = file_get_contents($this->root . '/core/modules/search/tests/UnicodeTest.txt');
    $basestrings = explode(chr(10), $input);
    $strings = [];
    $text_processor = \Drupal::service('search.text_processor');
    assert($text_processor instanceof SearchTextProcessorInterface);
    foreach ($basestrings as $key => $string) {
      if ($key % 2) {
        // Even line - should simplify down to a space.
        $simplified = $text_processor->analyze($string);
        $this->assertSame(' ', $simplified, "Line {$key} is excluded from the index");
      }
      else {
        // Odd line, should be word characters.
        // Split this into 30-character chunks, so we don't run into limits of
        // truncation in
        // \Drupal\search\SearchTextProcessorInterface::analyze().
        $start = 0;
        while ($start < mb_strlen($string)) {
          $new_string = mb_substr($string, $start, 30);
          // Special case: leading zeros are removed from numeric strings,
          // and there's one string in this file that is numbers starting with
          // zero, so prepend a 1 on that string.
          if (preg_match('/^[0-9]+$/', $new_string)) {
            $new_string = '1' . $new_string;
          }
          $strings[] = $new_string;
          $start += 30;
        }
      }
    }
    foreach ($strings as $key => $string) {
      $simplified = $text_processor->analyze($string);
      $this->assertGreaterThanOrEqual(mb_strlen($string), mb_strlen($simplified), "Nothing is removed from string $key.");
    }

    // Test the low-numbered ASCII control characters separately. They are not
    // in the text file because they are problematic for diff, especially \0.
    $string = '';
    for ($i = 0; $i < 32; $i++) {
      $string .= chr($i);
    }
    $this->assertSame(' ', $text_processor->analyze($string), 'Search simplify works for ASCII control characters.');
  }

  /**
   * Tests that text analysis does the right thing with punctuation.
   */
  public function testSearchTextProcessorPunctuation() {
    $cases = [
      ['20.03/94-28,876', '20039428876', 'Punctuation removed from numbers'],
      ['great...drupal--module', 'great drupal module', 'Multiple dot and dashes are word boundaries'],
      ['very_great-drupal.module', 'verygreatdrupalmodule', 'Single dot, dash, underscore are removed'],
      ['regular,punctuation;word', 'regular punctuation word', 'Punctuation is a word boundary'],
    ];

    $text_processor = \Drupal::service('search.text_processor');
    assert($text_processor instanceof SearchTextProcessorInterface);
    foreach ($cases as $case) {
      $out = trim($text_processor->analyze($case[0]));
      $this->assertEquals($case[1], $out, $case[2]);
    }
  }

}
