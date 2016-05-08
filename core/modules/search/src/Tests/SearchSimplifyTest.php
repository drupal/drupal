<?php

namespace Drupal\search\Tests;
use Drupal\Component\Utility\Unicode;

/**
 * Tests that the search_simply() function works as intended.
 *
 * @group search
 */
class SearchSimplifyTest extends SearchTestBase {
  /**
   * Tests that all Unicode characters simplify correctly.
   */
  function testSearchSimplifyUnicode() {
    // This test uses a file that was constructed so that the even lines are
    // boundary characters, and the odd lines are valid word characters. (It
    // was generated as a sequence of all the Unicode characters, and then the
    // boundary characters (punctuation, spaces, etc.) were split off into
    // their own lines).  So the even-numbered lines should simplify to nothing,
    // and the odd-numbered lines we need to split into shorter chunks and
    // verify that simplification doesn't lose any characters.
    $input = file_get_contents(\Drupal::root() . '/core/modules/search/tests/UnicodeTest.txt');
    $basestrings = explode(chr(10), $input);
    $strings = array();
    foreach ($basestrings as $key => $string) {
      if ($key % 2) {
        // Even line - should simplify down to a space.
        $simplified = search_simplify($string);
        $this->assertIdentical($simplified, ' ', "Line $key is excluded from the index");
      }
      else {
        // Odd line, should be word characters.
        // Split this into 30-character chunks, so we don't run into limits
        // of truncation in search_simplify().
        $start = 0;
        while ($start < Unicode::strlen($string)) {
          $newstr = Unicode::substr($string, $start, 30);
          // Special case: leading zeros are removed from numeric strings,
          // and there's one string in this file that is numbers starting with
          // zero, so prepend a 1 on that string.
          if (preg_match('/^[0-9]+$/', $newstr)) {
            $newstr = '1' . $newstr;
          }
          $strings[] = $newstr;
          $start += 30;
        }
      }
    }
    foreach ($strings as $key => $string) {
      $simplified = search_simplify($string);
      $this->assertTrue(Unicode::strlen($simplified) >= Unicode::strlen($string), "Nothing is removed from string $key.");
    }

    // Test the low-numbered ASCII control characters separately. They are not
    // in the text file because they are problematic for diff, especially \0.
    $string = '';
    for ($i = 0; $i < 32; $i++) {
      $string .= chr($i);
    }
    $this->assertIdentical(' ', search_simplify($string), 'Search simplify works for ASCII control characters.');
  }

  /**
   * Tests that search_simplify() does the right thing with punctuation.
   */
  function testSearchSimplifyPunctuation() {
    $cases = array(
      array('20.03/94-28,876', '20039428876', 'Punctuation removed from numbers'),
      array('great...drupal--module', 'great drupal module', 'Multiple dot and dashes are word boundaries'),
      array('very_great-drupal.module', 'verygreatdrupalmodule', 'Single dot, dash, underscore are removed'),
      array('regular,punctuation;word', 'regular punctuation word', 'Punctuation is a word boundary'),
    );

    foreach ($cases as $case) {
      $out = trim(search_simplify($case[0]));
      $this->assertEqual($out, $case[1], $case[2]);
    }
  }

}
