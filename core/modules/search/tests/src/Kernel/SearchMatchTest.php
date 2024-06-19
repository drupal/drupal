<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search\SearchIndexInterface;
use Drupal\search\SearchQuery;

// cspell:ignore cillum dolore enim veniam

/**
 * Indexes content and queries it.
 *
 * @group search
 */
class SearchMatchTest extends KernelTestBase {

  // The search index can contain different types of content. Typically the type
  // is 'node'. Here we test with _test_ and _test2_ as the type.
  const SEARCH_TYPE = '_test_';
  const SEARCH_TYPE_2 = '_test2_';
  const SEARCH_TYPE_JPN = '_test3_';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['search'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('search', ['search_index', 'search_dataset', 'search_total']);
    $this->installConfig(['search']);
  }

  /**
   * Tests search indexing.
   */
  public function testMatching(): void {
    $this->_setup();
    $this->_testQueries();
  }

  /**
   * Set up a small index of items to test against.
   */
  public function _setup() {
    $this->config('search.settings')->set('index.minimum_word_size', 3)->save();

    $search_index = \Drupal::service('search.index');
    assert($search_index instanceof SearchIndexInterface);
    for ($i = 1; $i <= 7; ++$i) {
      $search_index->index(static::SEARCH_TYPE, $i, LanguageInterface::LANGCODE_NOT_SPECIFIED, $this->getText($i));
    }
    for ($i = 1; $i <= 5; ++$i) {
      $search_index->index(static::SEARCH_TYPE_2, $i + 7, LanguageInterface::LANGCODE_NOT_SPECIFIED, $this->getText2($i));
    }
    // No getText builder function for Japanese text; just a simple array.
    foreach ([
      13 => '以呂波耳・ほへとち。リヌルヲ。',
      14 => 'ドルーパルが大好きよ！',
      15 => 'コーヒーとケーキ',
    ] as $i => $jpn) {
      $search_index->index(static::SEARCH_TYPE_JPN, $i, LanguageInterface::LANGCODE_NOT_SPECIFIED, $jpn);
    }
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
  public function getText($n) {
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
  public function getText2($n) {
    $words = explode(' ', "Dear King Philip came over from Germany swimming.");
    return implode(' ', array_slice($words, $n - 1, $n));
  }

  /**
   * Run predefine queries looking for indexed terms.
   */
  public function _testQueries() {
    // Note: OR queries that include short words in OR groups are only accepted
    // if the ORed terms are ANDed with at least one long word in the rest of
    // the query. Examples:
    // -  enim dolore OR ut = enim (dolore OR ut) = (enim dolor) OR (enim ut)
    // is good, and
    // -  dolore OR ut = (dolore) OR (ut)
    // is bad. This is a design limitation to avoid full table scans.
    $queries = [
      // Simple AND queries.
      'ipsum' => [1],
      'enim' => [4, 5, 6],
      'xxxxx' => [],
      'enim minim' => [5, 6],
      'enim xxxxx' => [],
      'dolore eu' => [7],
      'dolore xx' => [],
      'ut minim' => [5],
      'xx minim' => [],
      'enim veniam am minim ut' => [5],
      // Simple OR and AND/OR queries.
      'dolore OR ipsum' => [1, 2, 7],
      'dolore OR xxxxx' => [2, 7],
      'dolore OR ipsum OR enim' => [1, 2, 4, 5, 6, 7],
      'ipsum OR dolore sit OR cillum' => [2, 7],
      'minim dolore OR ipsum' => [7],
      'dolore OR ipsum veniam' => [7],
      'minim dolore OR ipsum OR enim' => [5, 6, 7],
      'dolore xx OR yy' => [],
      'xxxxx dolore OR ipsum' => [],
      // Sequence of OR queries.
      'minim' => [5, 6, 7],
      'minim OR xxxx' => [5, 6, 7],
      'minim OR xxxx OR minim' => [5, 6, 7],
      'minim OR xxxx minim' => [5, 6, 7],
      'minim OR xxxx minim OR yyyy' => [5, 6, 7],
      'minim OR xxxx minim OR cillum' => [6, 7, 5],
      'minim OR xxxx minim OR xxxx' => [5, 6, 7],
      // Negative queries.
      'dolore -sit' => [7],
      'dolore -eu' => [2],
      'dolore -xxxxx' => [2, 7],
      'dolore -xx' => [2, 7],
      // Phrase queries.
      '"dolore sit"' => [2],
      '"sit dolore"' => [],
      '"am minim veniam es"' => [6, 7],
      '"minim am veniam es"' => [],
      // Mixed queries.
      '"am minim veniam es" OR dolore' => [2, 6, 7],
      '"minim am veniam es" OR "dolore sit"' => [2],
      '"minim am veniam es" OR "sit dolore"' => [],
      '"am minim veniam es" -eu' => [6],
      '"am minim veniam" -"cillum dolore"' => [5, 6],
      '"am minim veniam" -"dolore cillum"' => [5, 6, 7],
      'xxxxx "minim am veniam es" OR dolore' => [],
      'xx "minim am veniam es" OR dolore' => [],
    ];
    $connection = Database::getConnection();
    foreach ($queries as $query => $results) {
      $result = $connection->select('search_index', 'i')
        ->extend(SearchQuery::class)
        ->searchExpression($query, static::SEARCH_TYPE)
        ->execute();

      $set = $result ? $result->fetchAll() : [];
      $this->_testQueryMatching($query, $set, $results);
      $this->_testQueryScores($query, $set, $results);
    }

    // These queries are run against the second index type, SEARCH_TYPE_2.
    $queries = [
      // Simple AND queries.
      'ipsum' => [],
      'enim' => [],
      'enim minim' => [],
      'dear' => [8],
      'germany' => [11, 12],
    ];
    foreach ($queries as $query => $results) {
      $result = $connection->select('search_index', 'i')
        ->extend(SearchQuery::class)
        ->searchExpression($query, static::SEARCH_TYPE_2)
        ->execute();

      $set = $result ? $result->fetchAll() : [];
      $this->_testQueryMatching($query, $set, $results);
      $this->_testQueryScores($query, $set, $results);
    }

    // These queries are run against the third index type, SEARCH_TYPE_JPN.
    $queries = [
      // Simple AND queries.
      '呂波耳' => [13],
      '以呂波耳' => [13],
      'ほへと　ヌルヲ' => [13],
      'とちリ' => [],
      'ドルーパル' => [14],
      'パルが大' => [14],
      'コーヒー' => [15],
      'ヒーキ' => [],
    ];
    foreach ($queries as $query => $results) {
      $result = $connection->select('search_index', 'i')
        ->extend(SearchQuery::class)
        ->searchExpression($query, static::SEARCH_TYPE_JPN)
        ->execute();

      $set = $result ? $result->fetchAll() : [];
      $this->_testQueryMatching($query, $set, $results);
      $this->_testQueryScores($query, $set, $results);
    }
  }

  /**
   * Tests the matching abilities of the engine.
   *
   * Verify if a query produces the correct results.
   */
  public function _testQueryMatching($query, $set, $results) {
    // Get result IDs.
    $found = [];
    foreach ($set as $item) {
      $found[] = $item->sid;
    }

    // Compare $results and $found.
    sort($found);
    sort($results);
    $this->assertEquals($found, $results, "Query matching '$query'");
  }

  /**
   * Tests the scoring abilities of the engine.
   *
   * Verify if a query produces normalized, monotonous scores.
   */
  public function _testQueryScores($query, $set, $results) {
    // Get result scores.
    $scores = [];
    foreach ($set as $item) {
      $scores[] = $item->calculated_score;
    }

    // Check order.
    $sorted = $scores;
    sort($sorted);
    $this->assertEquals($scores, array_reverse($sorted), "Query order '$query'");

    // Check range.
    $this->assertTrue(!count($scores) || (min($scores) > 0.0 && max($scores) <= 1.0001), "Query scoring '$query'");
  }

}
