<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchTokenizerTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests that CJK tokenizer works as intended.
 *
 * @group search
 */
class SearchTokenizerTest extends SearchTestBase {
  /**
   * Verifies that strings of CJK characters are tokenized.
   *
   * The search_simplify() function does special things with numbers, symbols,
   * and punctuation. So we only test that CJK characters that are not in these
   * character classes are tokenized properly. See PREG_CLASS_CKJ for more
   * information.
   */
  function testTokenizer() {
    // Set the minimum word size to 1 (to split all CJK characters) and make
    // sure CJK tokenizing is turned on.
    \Drupal::config('search.settings')
      ->set('index.minimum_word_size', 1)
      ->set('index.overlap_cjk', TRUE)
      ->save();
    $this->refreshVariables();

    // Create a string of CJK characters from various character ranges in
    // the Unicode tables.

    // Beginnings of the character ranges.
    $starts = array(
      'CJK unified' => 0x4e00,
      'CJK Ext A' => 0x3400,
      'CJK Compat' => 0xf900,
      'Hangul Jamo' => 0x1100,
      'Hangul Ext A' => 0xa960,
      'Hangul Ext B' => 0xd7b0,
      'Hangul Compat' => 0x3131,
      'Half non-punct 1' => 0xff21,
      'Half non-punct 2' => 0xff41,
      'Half non-punct 3' => 0xff66,
      'Hangul Syllables' => 0xac00,
      'Hiragana' => 0x3040,
      'Katakana' => 0x30a1,
      'Katakana Ext' => 0x31f0,
      'CJK Reserve 1' => 0x20000,
      'CJK Reserve 2' => 0x30000,
      'Bomofo' => 0x3100,
      'Bomofo Ext' => 0x31a0,
      'Lisu' => 0xa4d0,
      'Yi' => 0xa000,
    );

    // Ends of the character ranges.
    $ends = array(
      'CJK unified' => 0x9fcf,
      'CJK Ext A' => 0x4dbf,
      'CJK Compat' => 0xfaff,
      'Hangul Jamo' => 0x11ff,
      'Hangul Ext A' => 0xa97f,
      'Hangul Ext B' => 0xd7ff,
      'Hangul Compat' => 0x318e,
      'Half non-punct 1' => 0xff3a,
      'Half non-punct 2' => 0xff5a,
      'Half non-punct 3' => 0xffdc,
      'Hangul Syllables' => 0xd7af,
      'Hiragana' => 0x309f,
      'Katakana' => 0x30ff,
      'Katakana Ext' => 0x31ff,
      'CJK Reserve 1' => 0x2fffd,
      'CJK Reserve 2' => 0x3fffd,
      'Bomofo' => 0x312f,
      'Bomofo Ext' => 0x31b7,
      'Lisu' => 0xa4fd,
      'Yi' => 0xa48f,
    );

    // Generate characters consisting of starts, midpoints, and ends.
    $chars = array();
    $charcodes = array();
    foreach ($starts as $key => $value) {
      $charcodes[] = $starts[$key];
      $chars[] = $this->code2utf($starts[$key]);
      $mid = round(0.5 * ($starts[$key] + $ends[$key]));
      $charcodes[] = $mid;
      $chars[] = $this->code2utf($mid);
      $charcodes[] = $ends[$key];
      $chars[] = $this->code2utf($ends[$key]);
    }

    // Merge into a string and tokenize.
    $string = implode('', $chars);
    $out = trim(search_simplify($string));
    $expected = drupal_strtolower(implode(' ', $chars));

    // Verify that the output matches what we expect.
    $this->assertEqual($out, $expected, 'CJK tokenizer worked on all supplied CJK characters');
  }

  /**
   * Verifies that strings of non-CJK characters are not tokenized.
   *
   * This is just a sanity check - it verifies that strings of letters are
   * not tokenized.
   */
  function testNoTokenizer() {
    // Set the minimum word size to 1 (to split all CJK characters) and make
    // sure CJK tokenizing is turned on.
    \Drupal::config('search.settings')
      ->set('minimum_word_size', 1)
      ->set('overlap_cjk', TRUE)
      ->save();
    $this->refreshVariables();

    $letters = 'abcdefghijklmnopqrstuvwxyz';
    $out = trim(search_simplify($letters));

    $this->assertEqual($letters, $out, 'Letters are not CJK tokenized');
  }

  /**
   * Like PHP chr() function, but for unicode characters.
   *
   * chr() only works for ASCII characters up to character 255. This function
   * converts a number to the corresponding unicode character. Adapted from
   * functions supplied in comments on several functions on php.net.
   */
  function code2utf($num) {
    if ($num < 128) {
      return chr($num);
    }

    if ($num < 2048) {
      return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
    }

    if ($num < 65536) {
      return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
    }

    if ($num < 2097152) {
      return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
    }

    return '';
  }
}
