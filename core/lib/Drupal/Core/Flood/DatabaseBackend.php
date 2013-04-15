<?php

/**
 * @file
 * Definition of Drupal\Core\Flood\DatabaseBackend.
 */

namespace Drupal\Core\Flood;

use Symfony\Component\HttpFoundation\Request;
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
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Construct the DatabaseBackend.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the flood event
   *   information.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   */
  public function __construct(Connection $connection, Request $request) {
    $this->connection = $connection;
    $this->request = $request;
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::register().
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->request->getClientIp();
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
      $identifier = $this->request->getClientIp();
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
      $identifier = $this->request->getClientIp();
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
