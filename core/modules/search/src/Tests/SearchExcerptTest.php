<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchExcerptTest.
 */

namespace Drupal\search\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the search_excerpt() function.
 *
 * @group search
 */
class SearchExcerptTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('search', 'search_langcode_test');

  /**
   * Tests search_excerpt() with several simulated search keywords.
   *
   * Passes keywords and a sample marked up string, "The quick
   * brown fox jumps over the lazy dog", and compares it to the
   * correctly marked up string. The correctly marked up string
   * contains either highlighted keywords or the original marked
   * up string if no keywords matched the string.
   */
  function testSearchExcerpt() {
    // Make some text with entities and tags.
    $text = 'The <strong>quick</strong> <a href="#">brown</a> fox &amp; jumps <h2>over</h2> the lazy dog';
    // Note: The search_excerpt() function adds some extra spaces -- not
    // important for HTML formatting. Remove these for comparison.
    $expected = 'The quick brown fox &amp; jumps over the lazy dog';
    $result = preg_replace('| +|', ' ', search_excerpt('nothing', $text));
    $this->assertEqual(preg_replace('| +|', ' ', $result), $expected, 'Entire string, stripped of HTML tags, is returned when keyword is not found in short string');

    $result = preg_replace('| +|', ' ', search_excerpt('fox', $text));
    $this->assertEqual($result, 'The quick brown <strong>fox</strong> &amp; jumps over the lazy dog', 'Found keyword is highlighted');

    $expected = '<strong>The</strong> quick brown fox &amp; jumps over <strong>the</strong> lazy dog';
    $result = preg_replace('| +|', ' ', search_excerpt('The', $text));
    $this->assertEqual(preg_replace('| +|', ' ', $result), $expected, 'Keyword is highlighted at beginning of short string');

    $expected = 'The quick brown fox &amp; jumps over the lazy <strong>dog</strong>';
    $result = preg_replace('| +|', ' ', search_excerpt('dog', $text));
    $this->assertEqual(preg_replace('| +|', ' ', $result), $expected, 'Keyword is highlighted at end of short string');

    $longtext = str_repeat(str_replace('brown', 'silver', $text) . ' ', 10) . $text . str_repeat(' ' . str_replace('brown', 'pink', $text), 10);
    $result = preg_replace('| +|', ' ', search_excerpt('brown', $longtext));
    $expected = '… silver fox &amp; jumps over the lazy dog The quick <strong>brown</strong> fox &amp; jumps over the lazy dog The quick …';
    $this->assertEqual($result, $expected, 'Snippet around keyword in long text is correctly capped');

    $longtext = str_repeat($text . ' ', 10);
    $result = preg_replace('| +|', ' ', search_excerpt('nothing', $longtext));
    $expected = 'The quick brown fox &amp; jumps over the lazy dog';
    $this->assertTrue(strpos($result, $expected) === 0, 'When keyword is not found in long string, return value starts as expected');

    $entities = str_repeat('k&eacute;sz&iacute;t&eacute;se ', 20);
    $result = preg_replace('| +|', ' ', search_excerpt('nothing', $entities));
    $this->assertFalse(strpos($result, '&'), 'Entities are not present in excerpt');
    $this->assertTrue(strpos($result, 'í') > 0, 'Entities are converted in excerpt');

    // The node body that will produce this rendered $text is:
    // 123456789 HTMLTest +123456789+&lsquo;  +&lsquo;  +&lsquo;  +&lsquo;  +12345678  &nbsp;&nbsp;  +&lsquo;  +&lsquo;  +&lsquo;   &lsquo;
    $text = "<div class=\"field field-name-body field-type-text-with-summary field-label-hidden\"><div class=\"field-items\"><div class=\"field-item even\" property=\"content:encoded\"><p>123456789 HTMLTest +123456789+‘  +‘  +‘  +‘  +12345678      +‘  +‘  +‘   ‘</p>\n</div></div></div> ";
    $result = search_excerpt('HTMLTest', $text);
    $this->assertFalse(empty($result),  'Rendered Multi-byte HTML encodings are not corrupted in search excerpts');
  }

  /**
   * Tests search_excerpt() with search keywords matching simplified words.
   *
   * Excerpting should handle keywords that are matched only after going through
   * search_simplify(). This test passes keywords that match simplified words
   * and compares them with strings that contain the original unsimplified word.
   */
  function testSearchExcerptSimplified() {
    $lorem1 = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam vitae arcu at leo cursus laoreet. Curabitur dui tortor, adipiscing malesuada tempor in, bibendum ac diam. Cras non tellus a libero pellentesque condimentum. What is a Drupalism? Suspendisse ac lacus libero. Ut non est vel nisl faucibus interdum nec sed leo. Pellentesque sem risus, vulputate eu semper eget, auctor in libero.';
    $lorem2 = 'Ut fermentum est vitae metus convallis scelerisque. Phasellus pellentesque rhoncus tellus, eu dignissim purus posuere id. Quisque eu fringilla ligula. Morbi ullamcorper, lorem et mattis egestas, tortor neque pretium velit, eget eleifend odio turpis eu purus. Donec vitae metus quis leo pretium tincidunt a pulvinar sem. Morbi adipiscing laoreet mauris vel placerat. Nullam elementum, nisl sit amet scelerisque malesuada, dolor nunc hendrerit quam, eu ultrices erat est in orci.';

    // Make some text with some keywords that will get simplified.
    $text = $lorem1 . ' Number: 123456.7890 Hyphenated: one-two abc,def ' . $lorem2;
    // Note: The search_excerpt() function adds some extra spaces -- not
    // important for HTML formatting. Remove these for comparison.
    $result = preg_replace('| +|', ' ', search_excerpt('123456.7890', $text));
    $this->assertTrue(strpos($result, 'Number: <strong>123456.7890</strong>') !== FALSE, 'Numeric keyword is highlighted with exact match');

    $result = preg_replace('| +|', ' ', search_excerpt('1234567890', $text));
    $this->assertTrue(strpos($result, 'Number: <strong>123456.7890</strong>') !== FALSE, 'Numeric keyword is highlighted with simplified match');

    $result = preg_replace('| +|', ' ', search_excerpt('Number 1234567890', $text));
    $this->assertTrue(strpos($result, '<strong>Number</strong>: <strong>123456.7890</strong>') !== FALSE, 'Punctuated and numeric keyword is highlighted with simplified match');

    $result = preg_replace('| +|', ' ', search_excerpt('"Number 1234567890"', $text));
    $this->assertTrue(strpos($result, '<strong>Number: 123456.7890</strong>') !== FALSE, 'Phrase with punctuated and numeric keyword is highlighted with simplified match');

    $result = preg_replace('| +|', ' ', search_excerpt('"Hyphenated onetwo"', $text));
    $this->assertTrue(strpos($result, '<strong>Hyphenated: one-two</strong>') !== FALSE, 'Phrase with punctuated and hyphenated keyword is highlighted with simplified match');

    $result = preg_replace('| +|', ' ', search_excerpt('"abc def"', $text));
    $this->assertTrue(strpos($result, '<strong>abc,def</strong>') !== FALSE, 'Phrase with keyword simplified into two separate words is highlighted with simplified match');

    // Test phrases with characters which are being truncated.
    $result = preg_replace('| +|', ' ', search_excerpt('"ipsum _"', $text));
    $this->assertTrue(strpos($result, '<strong>ipsum</strong>') !== FALSE, 'Only valid part of the phrase is highlighted and invalid part containing "_" is ignored.');

    $result = preg_replace('| +|', ' ', search_excerpt('"ipsum 0000"', $text));
    $this->assertTrue(strpos($result, '<strong>ipsum</strong>') !== FALSE, 'Only valid part of the phrase is highlighted and invalid part "0000" is ignored.');

    // Test combination of the valid keyword and keyword containing only
    // characters which are being truncated during simplification.
    $result = preg_replace('| +|', ' ', search_excerpt('ipsum _', $text));
    $this->assertTrue(strpos($result, '<strong>ipsum</strong>') !== FALSE, 'Only valid keyword is highlighted and invalid keyword "_" is ignored.');

    $result = preg_replace('| +|', ' ', search_excerpt('ipsum 0000', $text));
    $this->assertTrue(strpos($result, '<strong>ipsum</strong>') !== FALSE, 'Only valid keyword is highlighted and invalid keyword "0000" is ignored.');

    // Test using the hook_search_preprocess() from the test module.
    // The hook replaces "finding" or "finds" with "find".
    // So, if we search for "find" or "finds" or "finding", we should
    // highlight "finding".
    $text = "this tests finding a string";
    $result = preg_replace('| +|', ' ', search_excerpt('finds', $text, 'ex'));
    $this->assertTrue(strpos($result, '<strong>finding</strong>') !== FALSE, 'Search excerpt works with preprocess hook, search for finds');
    $result = preg_replace('| +|', ' ', search_excerpt('find', $text, 'ex'));
    $this->assertTrue(strpos($result, '<strong>finding</strong>') !== FALSE, 'Search excerpt works with preprocess hook, search for find');

    // Just to be sure, test with the replacement at the beginning and end.
    $text = "finding at the beginning";
    $result = preg_replace('| +|', ' ', search_excerpt('finds', $text, 'ex'));
    $this->assertTrue(strpos($result, '<strong>finding</strong>') !== FALSE, 'Search excerpt works with preprocess hook, text at start');

    $text = "at the end finding";
    $result = preg_replace('| +|', ' ', search_excerpt('finds', $text, 'ex'));
    $this->assertTrue(strpos($result, '<strong>finding</strong>') !== FALSE, 'Search excerpt works with preprocess hook, text at end');

    // Testing with a one-to-many replacement: the test module replaces DIC
    // with Dependency Injection Container.
    $text = "something about the DIC is happening";
    $result = preg_replace('| +|', ' ', search_excerpt('Dependency', $text, 'ex'));
    $this->assertTrue(strpos($result, '<strong>DIC</strong>') !== FALSE, 'Search excerpt works with preprocess hook, acronym first word');

    $result = preg_replace('| +|', ' ', search_excerpt('Injection', $text, 'ex'));
    $this->assertTrue(strpos($result, '<strong>DIC</strong>') !== FALSE, 'Search excerpt works with preprocess hook, acronym second word');

    $result = preg_replace('| +|', ' ', search_excerpt('Container', $text, 'ex'));
    $this->assertTrue(strpos($result, '<strong>DIC</strong>') !== FALSE, 'Search excerpt works with preprocess hook, acronym third word');

    // Testing with a many-to-one replacement: the test module replaces
    // hypertext markup language with HTML.
    $text = "we always use hypertext markup language to describe things";
    $result = preg_replace('| +|', ' ', search_excerpt('html', $text, 'ex'));
    $this->assertTrue(strpos($result, '<strong>hypertext markup language</strong>') !== FALSE, 'Search excerpt works with preprocess hook, acronym many to one');

  }
}
