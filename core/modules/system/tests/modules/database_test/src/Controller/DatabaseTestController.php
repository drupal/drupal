<?php

namespace Drupal\database_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for database_test routes.
 */
class DatabaseTestController extends ControllerBase {

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
   * Creates temporary table and outputs the table name and its number of rows.
   *
   * We need to test that the table created is temporary, so we run it here, in a
   * separate menu callback request; After this request is done, the temporary
   * table should automatically dropped.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function dbQueryTemporary() {
    $table_name = $this->connection->queryTemporary('SELECT age FROM {test}', []);
    return new JsonResponse([
      'table_name' => $table_name,
      'row_count' => $this->connection->select($table_name)->countQuery()->execute()->fetchField(),
    ]);
  }

  /**
   * Runs a pager query and returns the results.
   *
   * This function does care about the page GET parameter, as set by the
   * simpletest HTTP call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function pagerQueryEven($limit) {
    $query = $this->connection->select('test', 't');
    $query
      ->fields('t', ['name'])
      ->orderBy('age');

    // This should result in 2 pages of results.
    $query = $query
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
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
   * simpletest HTTP call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function pagerQueryOdd($limit) {
    $query = $this->connection->select('test_task', 't');
    $query
      ->fields('t', ['task'])
      ->orderBy('pid');

    // This should result in 4 pages of results.
    $query = $query
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
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
   * simpletest HTTP call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function testTablesort() {
    $header = [
      'tid' => ['data' => t('Task ID'), 'field' => 'tid', 'sort' => 'desc'],
      'pid' => ['data' => t('Person ID'), 'field' => 'pid'],
      'task' => ['data' => t('Task'), 'field' => 'task'],
      'priority' => ['data' => t('Priority'), 'field' => 'priority'],
    ];

    $query = $this->connection->select('test_task', 't');
    $query
      ->fields('t', ['tid', 'pid', 'task', 'priority']);

    $query = $query
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
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
   * simpletest HTTP call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function testTablesortFirst() {
    $header = [
      'tid' => ['data' => t('Task ID'), 'field' => 'tid', 'sort' => 'desc'],
      'pid' => ['data' => t('Person ID'), 'field' => 'pid'],
      'task' => ['data' => t('Task'), 'field' => 'task'],
      'priority' => ['data' => t('Priority'), 'field' => 'priority'],
    ];

    $query = $this->connection->select('test_task', 't');
    $query
      ->fields('t', ['tid', 'pid', 'task', 'priority']);

    $query = $query
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->orderBy('priority');

    // We need all the results at once to check the sort.
    $tasks = $query->execute()->fetchAll();

    return new JsonResponse([
      'tasks' => $tasks,
    ]);
  }

}
