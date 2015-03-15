<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\TestMigrateExecutable.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\MigrateExecutable;

/**
 * Tests MigrateExecutable.
 */
class TestMigrateExecutable extends MigrateExecutable {

  /**
   * The (fake) number of seconds elapsed since the start of the test.
   *
   * @var int
   */
  protected $timeElapsed;

  /**
   * The fake memory usage in bytes.
   *
   * @var int
   */
  protected $memoryUsage;

  /**
   * The cleared memory usage.
   *
   * @var int
   */
  protected $clearedMemoryUsage;

  /**
   * Sets the string translation service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function setStringTranslation(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Allows access to protected timeOptionExceeded method.
   *
   * @return bool
   *   A threshold exceeded value.
   */
  public function timeOptionExceeded() {
    return parent::timeOptionExceeded();
  }

  /**
   * Allows access to set protected maxExecTime property.
   *
   * @param int $max_exec_time
   *   The value to set.
   */
  public function setMaxExecTime($max_exec_time) {
    $this->maxExecTime = $max_exec_time;
  }

  /**
   * Allows access to protected maxExecTime property.
   *
   * @return int
   *   The value of the protected property.
   */
  public function getMaxExecTime() {
    return $this->maxExecTime;
  }

  /**
   * Allows access to protected successesSinceFeedback property.
   *
   * @return int
   *   The value of the protected property.
   */
  public function getSuccessesSinceFeedback() {
    return $this->successesSinceFeedback;
  }

  /**
   * Allows access to protected totalSuccesses property.
   *
   * @return int
   *   The value of the protected property.
   */
  public function getTotalSuccesses() {
    return $this->totalSuccesses;
  }

  /**
   * Allows access to protected totalProcessed property.
   *
   * @return int
   *   The value of the protected property.
   */
  public function getTotalProcessed() {
    return $this->totalProcessed;
  }

  /**
   * Allows access to protected processedSinceFeedback property.
   *
   * @return int
   *   The value of the protected property.
   */
  public function getProcessedSinceFeedback() {
    return $this->processedSinceFeedback;
  }

  /**
   * Allows access to protected maxExecTimeExceeded method.
   *
   * @return bool
   *   The threshold exceeded value.
   */
  public function maxExecTimeExceeded() {
    return parent::maxExecTimeExceeded();
  }

  /**
   * Allows access to set protected source property.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The value to set.
   */
  public function setSource($source) {
    $this->source = $source;
  }

  /**
   * Allows access to protected sourceIdValues property.
   *
   * @param array $source_id_values
   *   The value to set.
   */
  public function setSourceIdValues($source_id_values) {
    $this->sourceIdValues = $source_id_values;
  }

  /**
   * Allows setting a fake elapsed time.
   *
   * @param int $time
   *   The time in seconds.
   */
  public function setTimeElapsed($time) {
    $this->timeElapsed = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeElapsed() {
    return $this->timeElapsed;
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(\Exception $exception, $save = TRUE) {
    $message = $exception->getMessage();
    if ($save) {
      $this->saveMessage($message);
    }
    $this->message->display($message);
  }

  /**
   * Allows access to the protected memoryExceeded method.
   *
   * @return bool
   *   The memoryExceeded value.
   */
  public function memoryExceeded() {
    return parent::memoryExceeded();
  }

  /**
   * {@inheritdoc}
   */
  protected function attemptMemoryReclaim() {
    return $this->clearedMemoryUsage;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMemoryUsage() {
    return $this->memoryUsage;
  }

  /**
   * Sets the fake memory usage.
   *
   * @param int $memory_usage
   *   The fake memory usage value.
   * @param int $cleared_memory_usage
   *   (optional) The fake cleared memory value.
   */
  public function setMemoryUsage($memory_usage, $cleared_memory_usage = NULL) {
    $this->memoryUsage = $memory_usage;
    $this->clearedMemoryUsage = $cleared_memory_usage;
  }

  /**
   * Sets the memory limit.
   *
   * @param int $memory_limit
   *   The memory limit.
   */
  public function setMemoryLimit($memory_limit) {
    $this->memoryLimit = $memory_limit;
  }

  /**
   * Sets the memory threshold.
   *
   * @param float $threshold
   *   The new threshold.
   */
  public function setMemoryThreshold($threshold) {
    $this->memoryThreshold = $threshold;
  }

  /**
   * Sets the time threshold.
   *
   * @param float $threshold
   *   The new threshold.
   */
  public function setTimeThreshold($threshold) {
    $this->timeThreshold = $threshold;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatSize($size) {
    return $size;
  }

}
