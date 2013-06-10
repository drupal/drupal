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
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct the BanSubscriber.
   *
   * @param \Drupal\Core\Database\Connection $connection
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

  /**
   * Returns if this IP address is banned.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP address is banned, FALSE otherwise.
   */
  public function isBanned($ip) {
    return (bool) $this->connection->query("SELECT * FROM {ban_ip} WHERE ip = :ip", array(':ip' => $ip))->fetchField();
  }

  /**
   * Finds all banned IP addresses.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   The result of the database query.
   */
  public function findAll() {
    return $this->connection->query('SELECT * FROM {ban_ip}');
  }

  /**
   * Bans an IP address.
   *
   * @param string $ip
   *   The IP address to ban.
   */
  public function banIp($ip) {
    $this->connection->insert('ban_ip')
      ->fields(array('ip' => $ip))
      ->execute();
  }

  /**
   * Unbans an IP address.
   *
   * @param string $id
   *   The IP address to unban.
   */
  public function unbanIp($id) {
    $this->connection->delete('ban_ip')
      ->condition('ip', $id)
      ->execute();
  }

  /**
   * Finds a banned IP address by its ID.
   *
   * @param int $ban_id
   *   The ID for a banned IP address.
   *
   * @return string|false
   *   Either the banned IP address or FALSE if none exist with that ID.
   */
  public function findById($ban_id) {
    return $this->connection->query("SELECT ip FROM {ban_ip} WHERE iid = :iid", array(':iid' => $ban_id))->fetchField();
  }

}
