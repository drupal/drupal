<?php

/**
 * @file
 * Definition of Drupal\text\TextSummaryTest.
 */

namespace Drupal\text\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the text field summary.
 */
class TextSummaryTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Text summary',
      'description' => 'Test text_summary() with different strings and lengths.',
      'group' => 'Field types',
    );
  }

  function setUp() {
    parent::setUp();
    $this->article_creator = $this->drupalCreateUser(array('create article content', 'edit own article content'));
  }

  /**
   * Tests an edge case where the first sentence is a question and
   * subsequent sentences are not. This edge case is documented at
   * http://drupal.org/node/180425.
   */
  function testFirstSentenceQuestion() {
    $text = 'A question? A sentence. Another sentence.';
    $expected = 'A question? A sentence.';
    $this->callTextSummary($text, $expected, NULL, 30);
  }

  /**
   * Test summary with long example.
   */
  function testLongSentence() {
    $text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ' . // 125
            'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. ' . // 108
            'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' . // 103
            'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'; // 110
    $expected = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ' .
                'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. ' .
                'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.';
    // First three sentences add up to: 336, so add one for space and then 3 to get half-way into next word.
    $this->callTextSummary($text, $expected, NULL, 340);
  }

  /**
   * Test various summary length edge cases.
   */
  function testLength() {
    // This string tests a number of edge cases.
    $text = "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>";

    // The summaries we expect text_summary() to return when $size is the index
    // of each array item.
    // Using no text format:
    $expected = array(
      "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>",
      "<",
      "<p",
      "<p>",
      "<p>\n",
      "<p>\nH",
      "<p>\nHi",
      "<p>\nHi\n",
      "<p>\nHi\n<",
      "<p>\nHi\n</",
      "<p>\nHi\n</p",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>",
      "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>",
      "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>",
    );

    // And using a text format WITH the line-break and htmlcorrector filters.
    $expected_lb = array(
      "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>",
      "",
      "<p></p>",
      "<p></p>",
      "<p></p>",
      "<p></p>",
      "<p></p>",
      "<p>\nHi</p>",
      "<p>\nHi</p>",
      "<p>\nHi</p>",
      "<p>\nHi</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>",
      "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>",
      "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>",
      "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>",
    );

    // Test text_summary() for different sizes.
    for ($i = 0; $i <= 37; $i++) {
      $this->callTextSummary($text, $expected[$i],    NULL, $i);
      $this->callTextSummary($text, $expected_lb[$i], 'plain_text', $i);
      $this->callTextSummary($text, $expected_lb[$i], 'filtered_html', $i);
    }
  }

  /**
   * Calls text_summary() and asserts that the expected teaser is returned.
   */
  function callTextSummary($text, $expected, $format = NULL, $size = NULL) {
    $summary = text_summary($text, $format, $size);
    $this->assertIdentical($summary, $expected, t('Generated summary "@summary" matches expected "@expected".', array('@summary' => $summary, '@expected' => $expected)));
  }

  /**
   * Test sending only summary.
   */
  function testOnlyTextSummary() {
    // Login as article creator.
    $this->drupalLogin($this->article_creator);
    // Create article with summary but empty body.
    $summary = $this->randomName();
    $edit = array(
      "title" => $this->randomName(),
      "body[und][0][summary]" => $summary,
    );
    $this->drupalPost('node/add/article', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title']);

    $this->assertIdentical($node->body['und'][0]['summary'], $summary, t('Article with with summary and no body has been submitted.'));
  }
}
