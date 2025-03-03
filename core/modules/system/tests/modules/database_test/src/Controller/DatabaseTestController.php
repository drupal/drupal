<?php

declare(strict_types=1);

namespace Drupal\database_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for database_test routes.
 */
class DatabaseTestController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a DatabaseTestController object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Runs a pager query and returns the results.
   *
   * This function does care about the page GET parameter, as set by the
   * test HTTP call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the names of the test entries.
   */
  public function pagerQueryEven($limit) {
    $query = $this->connection->select('test', 't');
    $query
      ->fields('t', ['name'])
      ->orderBy('age');

    // This should result in 2 pages of results.
    $query = $query
      ->extend(PagerSelectExtender::class)
      ->limit($limit);

    $names = $query->execute()->fetchCol();

    return new JsonResponse([
      'names' => $names,
    ]);
  }

  /**
   * Runs a pager query and returns the results.
   *
   * This function does care about the page GET parameter, as set by the
   * test HTTP call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the names of the test entries.
   */
  public function pagerQueryOdd($limit) {
    $query = $this->connection->select('test_task', 't');
    $query
      ->fields('t', ['task'])
      ->orderBy('pid');

    // This should result in 4 pages of results.
    $query = $query
      ->extend(PagerSelectExtender::class)
      ->limit($limit);

    $names = $query->execute()->fetchCol();

    return new JsonResponse([
      'names' => $names,
    ]);
  }

  /**
   * Runs a tablesort query and returns the results.
   *
   * This function does care about the page GET parameter, as set by the
   * test HTTP call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the test tasks.
   */
  public function testTablesort() {
    $header = [
      'tid' => ['data' => $this->t('Task ID'), 'field' => 'tid', 'sort' => 'desc'],
      'pid' => ['data' => $this->t('Person ID'), 'field' => 'pid'],
      'task' => ['data' => $this->t('Task'), 'field' => 'task'],
      'priority' => ['data' => $this->t('Priority'), 'field' => 'priority'],
    ];

    $query = $this->connection->select('test_task', 't');
    $query
      ->fields('t', ['tid', 'pid', 'task', 'priority']);

    $query = $query
      ->extend(TableSortExtender::class)
      ->orderByHeader($header);

    // We need all the results at once to check the sort.
    $tasks = $query->execute()->fetchAll();

    return new JsonResponse([
      'tasks' => $tasks,
    ]);
  }

  /**
   * Runs a tablesort query with a second order_by after and returns the results.
   *
   * This function does care about the page GET parameter, as set by the
   * test HTTP call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the test tasks.
   */
  public function testTablesortFirst() {
    $header = [
      'tid' => ['data' => $this->t('Task ID'), 'field' => 'tid', 'sort' => 'desc'],
      'pid' => ['data' => $this->t('Person ID'), 'field' => 'pid'],
      'task' => ['data' => $this->t('Task'), 'field' => 'task'],
      'priority' => ['data' => $this->t('Priority'), 'field' => 'priority'],
    ];

    $query = $this->connection->select('test_task', 't');
    $query
      ->fields('t', ['tid', 'pid', 'task', 'priority']);

    $query = $query
      ->extend(TableSortExtender::class)
      ->orderByHeader($header)
      ->orderBy('priority');

    // We need all the results at once to check the sort.
    $tasks = $query->execute()->fetchAll();

    return new JsonResponse([
      'tasks' => $tasks,
    ]);
  }

}
