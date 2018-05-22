<?php

namespace Drupal\statistics;

/**
 * Value object for passing statistic results.
 */
class StatisticsViewsResult {

  /**
   * @var int
   */
  protected $totalCount;

  /**
   * @var int
   */
  protected $dayCount;

  /**
   * @var int
   */
  protected $timestamp;

  public function __construct($total_count, $day_count, $timestamp) {
    $this->totalCount = (int) $total_count;
    $this->dayCount = (int) $day_count;
    $this->timestamp = (int) $timestamp;
  }

  /**
   * Total number of times the entity has been viewed.
   *
   * @return int
   */
  public function getTotalCount() {
    return $this->totalCount;
  }


  /**
   * Total number of times the entity has been viewed "today".
   *
   * @return int
   */
  public function getDayCount() {
    return $this->dayCount;
  }


  /**
   * Timestamp of when the entity was last viewed.
   *
   * @return int
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

}
