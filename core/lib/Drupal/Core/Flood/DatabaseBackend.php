<?php

/**
 * @file
 * Contains \Drupal\Core\Flood\DatabaseBackend.
 */

namespace Drupal\Core\Flood;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Database\Connection;

/**
 * Defines the database flood backend. This is the default Drupal backend.
 */
class DatabaseBackend implements FloodInterface {

  /**
   * The database connection used to store flood event information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct the DatabaseBackend.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the flood event
   *   information.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(Connection $connection, RequestStack $request_stack) {
    $this->connection = $connection;
    $this->requestStack = $request_stack;
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::register().
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $this->connection->insert('flood')
      ->fields(array(
        'event' => $name,
        'identifier' => $identifier,
        'timestamp' => REQUEST_TIME,
        'expiration' => REQUEST_TIME + $window,
      ))
      ->execute();
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::clear().
   */
  public function clear($name, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $this->connection->delete('flood')
      ->condition('event', $name)
      ->condition('identifier', $identifier)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::isAllowed().
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $number = $this->connection->select('flood', 'f')
      ->condition('event', $name)
      ->condition('identifier', $identifier)
      ->condition('timestamp', REQUEST_TIME - $window, '>')
      ->countQuery()
      ->execute()
      ->fetchField();
    return ($number < $threshold);
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::garbageCollection().
   */
  public function garbageCollection() {
    return $this->connection->delete('flood')
      ->condition('expiration', REQUEST_TIME, '<')
      ->execute();
  }

}
