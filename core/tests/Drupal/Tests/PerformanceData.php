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
   * The number of database queries recorded.
   */
  protected int $queryCount = 0;

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
   * Gets the stylesheet request count.
   *
   * @return int
   *   The number of stylesheet requests recorded.
   */
  public function getStylesheetCount(): int {
    return $this->stylesheetCount;
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
   * Gets the script request count.
   *
   * @return int
   *   The number of script requests recorded.
   */
  public function getScriptCount(): int {
    return $this->scriptCount;
  }

  /**
   * Sets the query count.
   *
   * @param int $count
   *   The number of database queries recorded.
   */
  public function setQueryCount(int $count): void {
    $this->queryCount = $count;
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
