<?php

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
