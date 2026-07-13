<?php

declare(strict_types=1);

namespace Drupal\TestTools\TestRunner;

use Drupal\Core\Test\TestRun;
use Drupal\Core\Test\TestRunResultsStorageInterface;

/**
 * Implements an in-memory test run results storage.
 *
 * @internal
 */
class MemoryTestRunResultsStorage implements TestRunResultsStorageInterface {

  /**
   * Test run id index.
   */
  private int $id = 1;

  /**
   * Test run results storage.
   */
  private array $testId;

  /**
   * {@inheritdoc}
   */
  public function createNew(): int {
    $id = $this->id++;
    $this->testId[$id] = [
      'log' => [],
    ];
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function setDatabasePrefix(TestRun $test_run, string $database_prefix): void {
    $this->testId[$test_run->id()]['last_prefix'] = $database_prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function insertLogEntry(TestRun $test_run, array $entry): bool {
    $entry['test_id'] = $test_run->id();
    $entry = array_merge([
      'function' => 'Unknown',
      'line' => 0,
      'file' => 'Unknown',
    ], $entry);

    $this->testId[$test_run->id()]['log'][] = $entry;

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeResults(TestRun $test_run): int {
    if (!isset($this->testId[$test_run->id()])) {
      return 0;
    }
    unset($this->testId[$test_run->id()]);
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogEntriesByTestClass(TestRun $test_run): array {
    $result = [];
    foreach ($this->testId[$test_run->id()]['log'] as $message_id => $log) {
      $entry = array_merge([
        'time' => '0',
        'exit_code' => '0',
      ], $log);
      $entry['message_id'] = (string) $message_id;
      $entry['test_id'] = (string) $entry['test_id'];
      $entry['line'] = (string) $entry['line'];
      $result[] = (object) $entry;
    }
    usort($result, function (object $a, object $b): int {
      if ($a->test_class == $b->test_class) {
        if ($a->message_id == $b->message_id) {
          return 0;
        }
        return ($a->message_id < $b->message_id) ? -1 : 1;
      }
      return strcmp($a->test_class, $b->test_class);
    });
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTestRunState(TestRun $test_run): array {
    // Identify the latest 'message_id' given the test_id.
    $max_message_id = count($this->testId[$test_run->id()]['log']) - 1;
    return [
      'db_prefix' => $this->testId[$test_run->id()]['last_prefix'],
      'test_class' => $this->testId[$test_run->id()]['log'][$max_message_id]['test_class'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildTestingResultsEnvironment(bool $keep_results): void {
    if (!$keep_results) {
      $this->id = 1;
      $this->testId = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateTestingResultsEnvironment(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanUp(): int {
    $count = count($this->testId);
    $this->buildTestingResultsEnvironment(FALSE);
    return $count;
  }

}
