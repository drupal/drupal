<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;

/**
 * Defines a manager to run readiness checkers.
 */
class ReadinessCheckerManager {

  /**
   * The key/value expirable storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * An array of active checkers.
   *
   * The keys are integers that indicate priority. Values are arrays of
   * ReadinessCheckerInterface objects.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface[][]
   */
  protected $checkersByPriority = [];

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The number of hours to store results.
   *
   * @var int
   */
  protected $storeResultsHours;

  /**
   * Constructs a ReadinessCheckerManager.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable_factory
   *   The key/value expirable factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param int $store_results_hours
   *   The number of hours to store results.
   */
  public function __construct(KeyValueExpirableFactoryInterface $key_value_expirable_factory, TimeInterface $time, int $store_results_hours) {
    $this->keyValueExpirable = $key_value_expirable_factory->get('auto_updates');
    $this->time = $time;
    $this->storeResultsHours = $store_results_hours;
  }

  /**
   * Appends a checker to the checker chain.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface $checker
   *   The checker interface to be appended to the checker chain.
   * @param int $priority
   *   (optional) The priority of the checker being added. Defaults to 0.
   *   Readiness checkers with larger priorities will run first within a
   *   category.
   */
  public function addChecker(ReadinessCheckerInterface $checker, int $priority = 0): void {
    $this->checkersByPriority[$priority][] = $checker;
    ksort($this->checkersByPriority);
  }

  /**
   * Runs the result checkers.
   *
   * @return $this
   */
  public function run(): self {
    $sorted_checkers = $this->getSortedCheckers();
    $results = [];
    foreach ($sorted_checkers as $checker) {
      if ($result = $checker->getResult()) {
        $results[$result->getCheckerId()] = $result;
      }
    }

    $this->keyValueExpirable->setWithExpire(
      'readiness_check_last_run',
      [
        'results' => $results,
        'checkers' => $this->getCurrentCheckerIds(),
      ],
      $this->storeResultsHours * 60 * 60
    );
    $this->keyValueExpirable->set('readiness_check_timestamp', $this->time->getRequestTime());
    return $this;
  }

  /**
   * Runs the readiness checkers if there are no valid results.
   *
   * @return $this
   */
  public function runIfNeeded(): self {
    if ($this->getResults() === NULL) {
      $this->run();
    }
    return $this;
  }

  /**
   * Gets the readiness checker results from the last run.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]|
   *   The result objects for the readiness checkers or NULL if no results are
   *   available.
   */
  public function getResults(): ?array {
    $last_run = $this->keyValueExpirable->get('readiness_check_last_run');

    // If the checkers have not changed return the results.
    if ($last_run && $last_run['checkers'] === $this->getCurrentCheckerIds()) {
      return $last_run['results'];
    }
    return NULL;
  }

  /**
   * Gets the timestamp of the most recent run.
   *
   * @return int|null
   *   The timestamp of the most recently completed run, or NULL if no run has
   *   been completed.
   */
  public function getMostRecentRunTime(): ?int {
    return $this->keyValueExpirable->get('readiness_check_timestamp');
  }

  /**
   * Sorts checkers according to priority.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface[]
   *   A sorted array of checker objects ordered by priority.
   */
  protected function getSortedCheckers(): array {
    $sorted = [];
    foreach ($this->checkersByPriority as $checkers) {
      $sorted = array_merge($sorted, $checkers);
    }
    return $sorted;
  }

  /**
   * Gets the current checker service IDs.
   *
   * @return string
   *   A concatenated list of checker service IDs delimited by '::'.
   */
  protected function getCurrentCheckerIds(): string {
    $service_ids = [];
    foreach ($this->getSortedCheckers() as $checker) {
      $service_ids[] = $checker->_serviceId;
    }
    return implode('::', $service_ids);
  }

}
