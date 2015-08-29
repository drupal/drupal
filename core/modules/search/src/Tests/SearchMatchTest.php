<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchMatchTest.
 */

namespace Drupal\search\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\KernelTestBase;

// The search index can contain different types of content. Typically the type
// is 'node'. Here we test with _test_ and _test2_ as the type.
const SEARCH_TYPE = '_test_';
const SEARCH_TYPE_2 = '_test2_';
const SEARCH_TYPE_JPN = '_test3_';

/**
 * Indexes content and queries it.
 *
 * @group search
 */
class SearchMatchTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['search'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('search', ['search_index', 'search_dataset', 'search_total']);
    $this->installConfig(['search']);
  }

  /**
   * Test search indexing.
   */
  function testMatching() {
    $this->_setup();
    $this->_testQueries();
  }

  /**
   * Set up a small index of items to test against.
   */
  function _setup() {
    $this->config('search.settings')->set('index.minimum_word_size', 3)->save();

    for ($i = 1; $i <= 7; ++$i) {
      search_index(SEARCH_TYPE, $i, LanguageInterface::LANGCODE_NOT_SPECIFIED, $this->getText($i));
    }
    for ($i = 1; $i <= 5; ++$i) {
      search_index(SEARCH_TYPE_2, $i + 7, LanguageInterface::LANGCODE_NOT_SPECIFIED, $this->getText2($i));
    }
    // No getText builder function for Japanese text; just a simple array.
    foreach (array(
      13 => '以呂波耳・ほへとち。リヌルヲ。',
      14 => 'ドルーパルが大好きよ！',
      15 => 'コーヒーとケーキ',
    ) as $i => $jpn) {
      search_index(SEARCH_TYPE_JPN, $i, LanguageInterface::LANGCODE_NOT_SPECIFIED, $jpn);
    }
    search_update_totals();
  }

  /**
   * _test_: Helper method for generating snippets of content.
   *
   * Generated items to test against:
   *   1  ipsum
   *   2  dolore sit
   *   3  sit am ut
   *   4  am ut enim am
   *   5  ut enim am minim veniam
   *   6  enim am minim veniam es cillum
   *   7  am minim veniam es cillum dolore eu
   */
  function getText($n) {
    $words = explode(' ', "Ipsum dolore sit am. Ut enim am minim veniam. Es cillum dolore eu.");
    return implode(' ', array_slice($words, $n - 1, $n));
  }

  /**
   * _test2_: Helper method for generating snippets of content.
   *
   * Generated items to test against:
   *   8  dear
   *   9  king philip
   *   10 philip came over
   *   11 came over from germany
   *   12 over from germany swimming
   */
  function getText2($n) {
    $words = explode(' ', "Dear King Philip came over from Germany swimming.");
    return implode(' ', array_slice($words, $n - 1, $n));
  }

  /**
   * Run predefine queries looking for indexed terms.
   */
  function _testQueries() {
    // Note: OR queries that include short words in OR groups are only accepted
    // if the ORed terms are ANDed with at least one long word in the rest of
    // the query. Examples:
    //   enim dolore OR ut = enim (dolore OR ut) = (enim dolor) OR (enim ut)
    // is good, and
    //   dolore OR ut = (dolore) OR (ut)
    // is bad. This is a design limitation to avoid full table scans.
    $queries = array(
      // Simple AND queries.
      'ipsum' => array(1),
      'enim' => array(4, 5, 6),
      'xxxxx' => array(),
      'enim minim' => array(5, 6),
      'enim xxxxx' => array(),
      'dolore eu' => array(7),
      'dolore xx' => array(),
      'ut minim' => array(5),
      'xx minim' => array(),
      'enim veniam am minim ut' => array(5),
      // Simple OR and AND/OR queries.
      'dolore OR ipsum' => array(1, 2, 7),
      'dolore OR xxxxx' => array(2, 7),
      'dolore OR ipsum OR enim' => array(1, 2, 4, 5, 6, 7),
      'ipsum OR dolore sit OR cillum' => array(2, 7),
      'minim dolore OR ipsum' => array(7),
      'dolore OR ipsum veniam' => array(7),
      'minim dolore OR ipsum OR enim' => array(5, 6, 7),
      'dolore xx OR yy' => array(),
      'xxxxx dolore OR ipsum' => array(),
      // Sequence of OR queries.
      'minim' => array(5, 6, 7),
      'minim OR xxxx' => array(5, 6, 7),
      'minim OR xxxx OR minim' => array(5, 6, 7),
      'minim OR xxxx minim' => array(5, 6, 7),
      'minim OR xxxx minim OR yyyy' => array(5, 6, 7),
      'minim OR xxxx minim OR cillum' => array(6, 7, 5),
      'minim OR xxxx minim OR xxxx' => array(5, 6, 7),
      // Negative queries.
      'dolore -sit' => array(7),
      'dolore -eu' => array(2),
      'dolore -xxxxx' => array(2, 7),
      'dolore -xx' => array(2, 7),
      // Phrase queries.
      '"dolore sit"' => array(2),
      '"sit dolore"' => array(),
      '"am minim veniam es"' => array(6, 7),
      '"minim am veniam es"' => array(),
      // Mixed queries.
      '"am minim veniam es" OR dolore' => array(2, 6, 7),
      '"minim am veniam es" OR "dolore sit"' => array(2),
      '"minim am veniam es" OR "sit dolore"' => array(),
      '"am minim veniam es" -eu' => array(6),
      '"am minim veniam" -"cillum dolore"' => array(5, 6),
      '"am minim veniam" -"dolore cillum"' => array(5, 6, 7),
      'xxxxx "minim am veniam es" OR dolore' => array(),
      'xx "minim am veniam es" OR dolore' => array()
    );
    foreach ($queries as $query => $results) {
      $result = db_select('search_index', 'i')
        ->extend('Drupal\search\SearchQuery')
        ->searchExpression($query, SEARCH_TYPE)
        ->execute();

      $set = $result ? $result->fetchAll() : array();
      $this->_testQueryMatching($query, $set, $results);
      $this->_testQueryScores($query, $set, $results);
    }

    // These queries are run against the second index type, SEARCH_TYPE_2.
    $queries = array(
      // Simple AND queries.
      'ipsum' => array(),
      'enim' => array(),
      'enim minim' => array(),
      'dear' => array(8),
      'germany' => array(11, 12),
    );
    foreach ($queries as $query => $results) {
      $result = db_select('search_index', 'i')
        ->extend('Drupal\search\SearchQuery')
        ->searchExpression($query, SEARCH_TYPE_2)
        ->execute();

      $set = $result ? $result->fetchAll() : array();
      $this->_testQueryMatching($query, $set, $results);
      $this->_testQueryScores($query, $set, $results);
    }

    // These queries are run against the third index type, SEARCH_TYPE_JPN.
    $queries = array(
      // Simple AND queries.
      '呂波耳' => array(13),
      '以呂波耳' => array(13),
      'ほへと　ヌルヲ' => array(13),
      'とちリ' => array(),
      'ドルーパル' => array(14),
      'パルが大' => array(14),
      'コーヒー' => array(15),
      'ヒーキ' => array(),
    );
    foreach ($queries as $query => $results) {
      $result = db_select('search_index', 'i')
        ->extend('Drupal\search\SearchQuery')
        ->searchExpression($query, SEARCH_TYPE_JPN)
        ->execute();

      $set = $result ? $result->fetchAll() : array();
      $this->_testQueryMatching($query, $set, $results);
      $this->_testQueryScores($query, $set, $results);
    }
  }

  /**
   * Test the matching abilities of the engine.
   *
   * Verify if a query produces the correct results.
   */
  function _testQueryMatching($query, $set, $results) {
    // Get result IDs.
    $found = array();
    foreach ($set as $item) {
      $found[] = $item->sid;
    }

    // Compare $results and $found.
    sort($found);
    sort($results);
    $this->assertEqual($found, $results, "Query matching '$query'");
  }

  /**
   * Test the scoring abilities of the engine.
   *
   * Verify if a query produces normalized, monotonous scores.
   */
  function _testQueryScores($query, $set, $results) {
    // Get result scores.
    $scores = array();
    foreach ($set as $item) {
      $scores[] = $item->calculated_score;
    }

    // Check order.
    $sorted = $scores;
    sort($sorted);
    $this->assertEqual($scores, array_reverse($sorted), "Query order '$query'");

    // Check range.
    $this->assertEqual(!count($scores) || (min($scores) > 0.0 && max($scores) <= 1.0001), TRUE, "Query scoring '$query'");
  }
}
