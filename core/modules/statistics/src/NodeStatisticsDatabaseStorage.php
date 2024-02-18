<?php

namespace Drupal\statistics;

use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

// cspell:ignore daycount totalcount

/**
 * Provides the default database storage backend for statistics.
 */
class NodeStatisticsDatabaseStorage implements StatisticsStorageInterface {

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs the statistics storage.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for the node view storage.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(Connection $connection, StateInterface $state, RequestStack $request_stack) {
    $this->connection = $connection;
    $this->state = $state;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function recordView($id) {
    return (bool) $this->connection
      ->merge('node_counter')
      ->key('nid', $id)
      ->fields([
        'daycount' => 1,
        'totalcount' => 1,
        'timestamp' => $this->getRequestTime(),
      ])
      ->expression('daycount', '[daycount] + 1')
      ->expression('totalcount', '[totalcount] + 1')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function fetchViews($ids) {
    $views = $this->connection
      ->select('node_counter', 'nc')
      ->fields('nc', ['totalcount', 'daycount', 'timestamp'])
      ->condition('nid', $ids, 'IN')
      ->execute()
      ->fetchAll();
    foreach ($views as $id => $view) {
      $views[$id] = new StatisticsViewsResult($view->totalcount, $view->daycount, $view->timestamp);
    }
    return $views;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchView($id) {
    $views = $this->fetchViews([$id]);
    return reset($views);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($order = 'totalcount', $limit = 5) {
    assert(in_array($order, ['totalcount', 'daycount', 'timestamp']), "Invalid order argument.");

    return $this->connection
      ->select('node_counter', 'nc')
      ->fields('nc', ['nid'])
      ->orderBy($order, 'DESC')
      ->range(0, $limit)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteViews($id) {
    return (bool) $this->connection
      ->delete('node_counter')
      ->condition('nid', $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function resetDayCount() {
    $statistics_timestamp = $this->state->get('statistics.day_timestamp', 0);
    if (($this->getRequestTime() - $statistics_timestamp) >= 86400) {
      $this->state->set('statistics.day_timestamp', $this->getRequestTime());
      $this->connection->update('node_counter')
        ->fields(['daycount' => 0])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function maxTotalCount() {
    $query = $this->connection->select('node_counter', 'nc');
    $query->addExpression('MAX([totalcount])');
    $max_total_count = (int) $query->execute()->fetchField();
    return $max_total_count;
  }

  /**
   * Get current request time.
   *
   * @return int
   *   Unix timestamp for current server request time.
   */
  protected function getRequestTime() {
    return $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
  }

}
