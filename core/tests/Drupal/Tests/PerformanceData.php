<?php

declare(strict_types=1);

namespace Drupal\Tests;

/**
 * Value object to store performance information collected from requests.
 *
 * @see Drupal\Tests\PerformanceTestTrait::collectPerformanceData().
 */
class PerformanceData {

  /**
   * The number of stylesheets requested.
   */
  protected int $stylesheetCount = 0;

  /**
   * The number of scripts requested.
   */
  protected int $scriptCount = 0;

  /**
   * The total stylesheet bytes requested.
   */
  protected int $stylesheetBytes = 0;

  /**
   * The total script bytes requested.
   */
  protected int $scriptBytes = 0;

  /**
   * The number of database queries recorded.
   */
  protected int $queryCount = 0;

  /**
   * The individual database queries recorded.
   */
  protected array $queries = [];

  /**
   * The number of cache gets recorded.
   */
  protected int $cacheGetCount = 0;

  /**
   * The number of cache sets recorded.
   */
  protected int $cacheSetCount = 0;

  /**
   * The number of cache deletes recorded.
   */
  protected int $cacheDeleteCount = 0;

  /**
   * The number of cache tag checksum checks.
   */
  protected int $cacheTagChecksumCount = 0;

  /**
   * The number of cache tag validity checks.
   */
  protected int $cacheTagIsValidCount = 0;

  /**
   * The number of cache tag invalidations.
   */
  protected int $cacheTagInvalidationCount = 0;

  /**
   * The original return value.
   */
  protected $returnValue;

  /**
   * Sets the stylesheet request count.
   *
   * @param int $count
   *   The number of stylesheet requests recorded.
   */
  public function setStylesheetCount(int $count): void {
    $this->stylesheetCount = $count;
  }

  /**
   * Sets the stylesheet bytes.
   *
   * @param int $bytes
   *   The stylesheet bytes recorded.
   */
  public function setStylesheetBytes(int $bytes): void {
    $this->stylesheetBytes = $bytes;
  }

  /**
   * Gets the stylesheet request count.
   *
   * @return int
   *   The number of stylesheet requests recorded.
   */
  public function getStylesheetCount(): int {
    return $this->stylesheetCount;
  }

  /**
   * Gets the stylesheet bytes count.
   *
   * @return int
   *   The stylesheet bytes recorded.
   */
  public function getStylesheetBytes(): int {
    return $this->stylesheetBytes;
  }

  /**
   * Sets the script request count.
   *
   * @param int $count
   *   The number of script requests recorded.
   */
  public function setScriptCount(int $count) {
    $this->scriptCount = $count;
  }

  /**
   * Sets the script bytes.
   *
   * @param int $bytes
   *   The script bytes recorded.
   */
  public function setScriptBytes(int $bytes): void {
    $this->scriptBytes = $bytes;
  }

  /**
   * Gets the script request count.
   *
   * @return int
   *   The number of script requests recorded.
   */
  public function getScriptCount(): int {
    return $this->scriptCount;
  }

  /**
   * Gets the script bytes count.
   *
   * @return int
   *   The script bytes recorded.
   */
  public function getScriptBytes(): int {
    return $this->scriptBytes;
  }

  /**
   * Logs a database query.
   *
   * @param string $query
   *   The database query recorded.
   */
  public function logQuery(string $query): void {
    $this->queries[] = $query;
    $this->queryCount++;
  }

  /**
   * Gets the queries.
   *
   * @return string[]
   *   The database queries recorded.
   */
  public function getQueries(): array {
    return $this->queries;
  }

  /**
   * Gets the query count.
   *
   * @return int
   *   The number of database queries recorded.
   */
  public function getQueryCount(): int {
    return $this->queryCount;
  }

  /**
   * Sets the cache get count.
   *
   * @param int $count
   *   The number of cache gets recorded.
   */
  public function setCacheGetCount(int $count): void {
    $this->cacheGetCount = $count;
  }

  /**
   * Gets the cache get count.
   *
   * @return int
   *   The number of cache gets recorded.
   */
  public function getCacheGetCount(): int {
    return $this->cacheGetCount;
  }

  /**
   * Sets the cache set count.
   *
   * @param int $count
   *   The number of cache sets recorded.
   */
  public function setCacheSetCount(int $count): void {
    $this->cacheSetCount = $count;
  }

  /**
   * Gets the cache set count.
   *
   * @return int
   *   The number of cache sets recorded.
   */
  public function getCacheSetCount(): int {
    return $this->cacheSetCount;
  }

  /**
   * Sets the cache delete count.
   *
   * @param int $count
   *   The number of cache deletes recorded.
   */
  public function setCacheDeleteCount(int $count): void {
    $this->cacheDeleteCount = $count;
  }

  /**
   * Gets the cache delete count.
   *
   * @return int
   *   The number of cache deletes recorded.
   */
  public function getCacheDeleteCount(): int {
    return $this->cacheDeleteCount;
  }

  /**
   * Sets the cache tag checksum count.
   *
   * @param int $count
   *   The number of cache tag checksum checks recorded.
   */
  public function setCacheTagChecksumCount(int $count): void {
    $this->cacheTagChecksumCount = $count;
  }

  /**
   * Gets the cache tag checksum count.
   *
   * @return int
   *   The number of cache tag checksum checks recorded.
   */
  public function getCacheTagChecksumCount(): int {
    return $this->cacheTagChecksumCount;
  }

  /**
   * Sets the cache tag isValid count.
   *
   * @param int $count
   *   The number of cache tag isValid checks recorded.
   */
  public function setCacheTagIsValidCount(int $count): void {
    $this->cacheTagIsValidCount = $count;
  }

  /**
   * Gets the cache tag isValid count.
   *
   * @return int
   *   The number of cache tag isValid checks recorded.
   */
  public function getCacheTagIsValidCount(): int {
    return $this->cacheTagIsValidCount;
  }

  /**
   * Sets the cache tag invalidation count.
   *
   * @param int $count
   *   The number of cache tag invalidations recorded.
   */
  public function setCacheTagInvalidationCount(int $count): void {
    $this->cacheTagInvalidationCount = $count;
  }

  /**
   * Gets the cache tag invalidation count.
   *
   * @return int
   *   The number of cache tag invalidations recorded.
   */
  public function getCacheTagInvalidationCount(): int {
    return $this->cacheTagInvalidationCount;
  }

  /**
   * Sets the original return value.
   *
   * @param mixed $return
   *   The original return value.
   */
  public function setReturnValue($return): void {
    $this->returnValue = $return;
  }

  /**
   * Gets the original return value.
   *
   * PerformanceTestTrait::collectPerformanceData() takes a callable as its
   * argument. This method allows the original return value of the callable to
   * be retrieved.
   *
   * @return mixed
   *   The original return value.
   */
  public function getReturnValue() {
    return $this->returnValue;
  }

}
