<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\TrackerUser.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the tracker_user table.
 */
class TrackerUser extends DrupalDumpBase {

  public function load() {
    $this->createTable("tracker_user", array(
      'primary key' => array(
        'nid',
        'uid',
      ),
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'published' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
          'default' => '0',
        ),
        'changed' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("tracker_user")->fields(array(
      'nid',
      'uid',
      'published',
      'changed',
    ))
    ->values(array(
      'nid' => '1',
      'uid' => '1',
      'published' => '1',
      'changed' => '1421727536',
    ))->execute();
  }

}
#6fb17f49221d202409a1298360628d70
