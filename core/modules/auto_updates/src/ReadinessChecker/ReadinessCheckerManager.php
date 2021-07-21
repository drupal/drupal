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
  protected $resultsTimeToLive;

  /**
   * Constructs a ReadinessCheckerManager.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable_factory
   *   The key/value expirable factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param int $results_time_to_live
   *   The number of hours to store results.
   */
  public function __construct(KeyValueExpirableFactoryInterface $key_value_expirable_factory, TimeInterface $time, int $results_time_to_live) {
    $this->keyValueExpirable = $key_value_expirable_factory->get('auto_updates');
    $this->time = $time;
    $this->resultsTimeToLive = $results_time_to_live;
  }

  /**
   * Adds a readiness checker.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface $checker
   *   The checker to add.
   * @param int $priority
   *   (optional) The priority of the checker being added. Defaults to 0.
   *   Readiness checkers with higher priorities will run first. It is not
   *   possible to sort checkers that have same priority. The relative order of
   *   checkers with the same priority should not be relied on.
   */
  public function addChecker(ReadinessCheckerInterface $checker, int $priority = 0): void {
    $this->checkersByPriority[$priority][] = $checker;
    ksort($this->checkersByPriority);
  }

  /**
   * Runs all readiness checkers and stores the results.
   *
   * @return $this
   */
  public function run(): self {
    $results = [];
    foreach ($this->getSortedCheckers() as $checker) {
      if ($checker_results = $checker->getResults()) {
        $results = array_merge($results, $checker_results);
      }
    }

    $this->keyValueExpirable->setWithExpire(
      'readiness_check_last_run',
      [
        'results' => $results,
        'checkers' => $this->getCurrentCheckerIds(),
      ],
      $this->resultsTimeToLive * 60 * 60
    );
    $this->keyValueExpirable->set('readiness_check_timestamp', $this->time->getRequestTime());
    return $this;
  }

  /**
   * Runs the readiness checkers if there no stored valid results.
   *
   * @return $this
   *
   * @see self::getResults()
   * @see self::getStoredValidResults()
   */
  public function runIfNoStoredResults(): self {
    if ($this->getResults() === NULL) {
      $this->run();
    }
    return $this;
  }

  /**
   * Gets the readiness checker results from the last run.
   *
   * @param int|null $severity
   *   (optional) The severity for the results to return. Should be one of the
   *   SystemManager::REQUIREMENT_* constants.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]|
   *   The result objects for the readiness checkers or NULL if no results are
   *   available or if the stored results are no longer valid.
   *
   * @see self::getStoredValidResults()
   */
  public function getResults(?int $severity = NULL): ?array {
    $results = $this->getStoredValidResults();
    if ($results !== NULL) {
      if ($severity !== NULL) {
        $results = array_filter($results, function ($result) use ($severity) {
          return $result->getSeverity() === $severity;
        });
      }
      return $results;
    }
    return NULL;
  }

  /**
   * Gets stored valid results, if any.
   *
   * The stored results are considered valid if the currently available
   * readiness checkers are the same as the last time the checkers were run.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]|null
   *   The stored results if available and still valid, otherwise null.
   */
  protected function getStoredValidResults(): ?array {
    $last_run = $this->keyValueExpirable->get('readiness_check_last_run');

    // If the checkers have not changed return the results.
    if ($last_run && $last_run['checkers'] === $this->getCurrentCheckerIds()) {
      return $last_run['results'];
    }
    return NULL;
  }

  /**
   * Gets the timestamp of the last run.
   *
   * @return int|null
   *   The timestamp of the last completed run, or NULL if no run has
   *   been completed.
   */
  public function getLastRunTime(): ?int {
    return $this->keyValueExpirable->get('readiness_check_timestamp');
  }

  /**
   * Sorts checkers according to priority.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface[]
   *   A sorted array of checker objects ordered by priority.
   */
  protected function getSortedCheckers(): array {
    return $this->checkersByPriority ? array_merge(...$this->checkersByPriority) : [];
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
