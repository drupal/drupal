<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\BlockNodeType.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the block_node_type table.
 */
class BlockNodeType extends DrupalDumpBase {

  public function load() {
    $this->createTable("block_node_type", array(
      'primary key' => array(
        'module',
        'delta',
        'type',
      ),
      'fields' => array(
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
        ),
        'delta' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("block_node_type")->fields(array(
      'module',
      'delta',
      'type',
    ))
    ->execute();
  }

}
#2e37638985a1b6e4e9734f60ecd857e5
