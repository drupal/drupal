<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Forum.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the forum table.
 */
class Forum extends DrupalDumpBase {

  public function load() {
    $this->createTable("forum", array(
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'tid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
      'primary key' => array(
        'vid',
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("forum")->fields(array(
      'nid',
      'vid',
      'tid',
    ))
    ->execute();
  }

}
#7cf66382d8b9f4f02726d56bf0068911
