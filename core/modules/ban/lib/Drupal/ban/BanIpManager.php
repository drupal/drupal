<?php

/**
 * @file
 * Definition of Drupal\ban\BanIpManager.
 */

namespace Drupal\ban;

use Drupal\Core\Database\Connection;

/**
 * Ban IP manager.
 */
class BanIpManager {

  /**
   * The database connection used to check the IP against.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct the BanSubscriber.
   *
   * @param Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to check the IP against.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Returns whether an IP address is blocked.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if access is denied, FALSE if access is allowed.
   */
  public function isDenied($ip) {
    $denied = $this->connection
      ->query('SELECT 1 FROM {ban_ip} WHERE ip = :ip', array(':ip' => $ip))
      ->fetchField();
    return (bool) $denied;
  }
}
