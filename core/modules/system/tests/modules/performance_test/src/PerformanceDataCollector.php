<?php

declare(strict_types=1);

namespace Drupal\performance_test;

use Drupal\Core\Database\Event\DatabaseEvent;
use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionFailureEvent;
use Drupal\Core\DestructableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Collects and stores performance data for database and cache operations.
 */
class PerformanceDataCollector implements EventSubscriberInterface, DestructableInterface {

  /**
   * Database events collected during the request.
   *
   * @var \Drupal\Core\Database\Event\DatabaseEvent[]
   */
  protected array $databaseEvents = [];

  /**
   * Cache operations collected during the request.
   */
  protected array $cacheOperations = [];

  /**
   * Cache tag operations collected during the request.
   */
  protected array $cacheTagOperations = [];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      StatementExecutionEndEvent::class => 'onDatabaseEvent',
      StatementExecutionFailureEvent::class => 'onDatabaseEvent',
    ];
  }

  /**
   * Logs database statements.
   */
  public function onDatabaseEvent(DatabaseEvent $event): void {
    // Use the event object as a value object.
    $this->databaseEvents[] = $event;
  }

  /**
   * Adds a cache operation.
   */
  public function addCacheOperation(array $operation) {
    $this->cacheOperations[] = $operation;
  }

  /**
   * Adds a cache tag operation.
   */
  public function addCacheTagOperation(array $operation) {
    $this->cacheTagOperations[] = $operation;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct(): void {
    // Get the events now before issuing any more database queries so that this
    // logging does not become part of the recorded data.
    $database_events = $this->databaseEvents;

    // Deliberately do not use an injected key value or lock service to avoid
    // any overhead up until this point.
    $lock = \Drupal::lock();

    // There are a finite number of requests, so if we don't get the lock just
    // wait for up to ten seconds then record the data anyway.
    if (!$lock->acquire('performance_test')) {
      $lock->wait('performance_test', 10);
    }
    $collection = \Drupal::keyValue('performance_test');
    $existing_data = $collection->get('performance_test_data') ?? [
      'database_events' => [],
      'cache_operations' => [],
      'cache_tag_operations' => [],
    ];
    $existing_data['database_events'] = array_merge($existing_data['database_events'], $database_events);
    $existing_data['cache_operations'] = array_merge($existing_data['cache_operations'], $this->cacheOperations);
    $existing_data['cache_tag_operations'] = array_merge($existing_data['cache_tag_operations'], $this->cacheTagOperations);
    $collection->set('performance_test_data', $existing_data);
    $lock->release('performance_test');
  }

}
