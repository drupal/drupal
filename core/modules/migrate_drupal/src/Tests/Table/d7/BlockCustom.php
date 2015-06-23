<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\BlockCustom.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the block_custom table.
 */
class BlockCustom extends DrupalDumpBase {

  public function load() {
    $this->createTable("block_custom", array(
      'primary key' => array(
        'bid',
      ),
      'fields' => array(
        'bid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'body' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'info' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'format' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("block_custom")->fields(array(
      'bid',
      'body',
      'info',
      'format',
    ))
    ->execute();
  }

}
#bcecada721307d09075575d51819ab41
