<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Queue.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the queue table.
 */
class Queue extends DrupalDumpBase {

  public function load() {
    $this->createTable("queue", array(
      'primary key' => array(
        'item_id',
      ),
      'fields' => array(
        'item_id' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'data' => array(
          'type' => 'blob',
          'not null' => FALSE,
          'length' => 100,
        ),
        'expire' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'created' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("queue")->fields(array(
      'item_id',
      'name',
      'data',
      'expire',
      'created',
    ))
    ->values(array(
      'item_id' => '45',
      'name' => 'update_fetch_tasks',
      'data' => 'a:8:{s:4:"name";s:4:"date";s:4:"info";a:6:{s:4:"name";s:4:"Date";s:7:"package";s:9:"Date/Time";s:7:"version";s:7:"7.x-2.8";s:7:"project";s:4:"date";s:9:"datestamp";s:10:"1406653438";s:16:"_info_file_ctime";i:1421694394;}s:9:"datestamp";s:10:"1406653438";s:8:"includes";a:2:{s:4:"date";s:4:"Date";s:8:"date_api";s:8:"Date API";}s:12:"project_type";s:6:"module";s:14:"project_status";b:1;s:10:"sub_themes";a:0:{}s:11:"base_themes";a:0:{}}',
      'expire' => '0',
      'created' => '1421843480',
    ))->values(array(
      'item_id' => '46',
      'name' => 'update_fetch_tasks',
      'data' => 'a:8:{s:4:"name";s:5:"email";s:4:"info";a:6:{s:4:"name";s:5:"Email";s:7:"package";s:6:"Fields";s:7:"version";s:7:"7.x-1.3";s:7:"project";s:5:"email";s:9:"datestamp";s:10:"1397134155";s:16:"_info_file_ctime";i:1421694395;}s:9:"datestamp";s:10:"1397134155";s:8:"includes";a:1:{s:5:"email";s:5:"Email";}s:12:"project_type";s:6:"module";s:14:"project_status";b:1;s:10:"sub_themes";a:0:{}s:11:"base_themes";a:0:{}}',
      'expire' => '0',
      'created' => '1421843480',
    ))->execute();
  }

}
#1041c7ebc45ffcc5952a6a089ff29e65
