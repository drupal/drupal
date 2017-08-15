<?php

namespace Drupal\ban\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 blocked IPs from database.
 *
 * @MigrateSource(
 *   id = "d7_blocked_ips",
 *   source_module = "system"
 * )
 */
class BlockedIps extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('blocked_ips', 'bi')->fields('bi', ['ip']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'ip' => $this->t('The blocked IP address.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['ip' => ['type' => 'string']];
  }

}
