<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\BlockedIps.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the blocked_ips table.
 */
class BlockedIps extends DrupalDumpBase {

  public function load() {
    $this->createTable("blocked_ips", array(
      'primary key' => array(
        'iid',
      ),
      'fields' => array(
        'iid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'ip' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '40',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("blocked_ips")->fields(array(
      'iid',
      'ip',
    ))
    ->values(array(
      'iid' => '1',
      'ip' => '111.111.111.111',
    ))->execute();
  }

}
#c9067ecae8a01a9ae2f2e260c258de23
