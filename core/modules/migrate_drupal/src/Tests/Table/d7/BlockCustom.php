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
    ->values(array(
      'bid' => '1',
      'body' => "A fellow jumped off a high wall\r\nAnd had a most terrible fall\r\nHe went back to bed\r\nWith a bump on his head\r\nThat's why you don't jump off a wall",
      'info' => 'Limerick',
      'format' => 'filtered_html',
    ))->execute();
  }

}
#d317035ee9af9b69d78ab3dd0c7072b9
