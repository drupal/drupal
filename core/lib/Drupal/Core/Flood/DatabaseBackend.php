<?php

/**
 * @file
 * Definition of Drupal\Core\Flood\DatabaseBackend.
 */

namespace Drupal\Core\Flood;

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
   * Construct the DatabaseBackend.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the flood event
   *   information.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Implements Drupal\Core\Flood\FloodInterface::register().
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = ip_address();
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
      $identifier = ip_address();
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
      $identifier = ip_address();
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
