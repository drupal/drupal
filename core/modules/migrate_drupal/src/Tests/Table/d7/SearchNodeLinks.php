<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\SearchNodeLinks.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the search_node_links table.
 */
class SearchNodeLinks extends DrupalDumpBase {

  public function load() {
    $this->createTable("search_node_links", array(
      'primary key' => array(
        'sid',
        'type',
        'nid',
      ),
      'fields' => array(
        'sid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '16',
          'default' => '',
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'caption' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("search_node_links")->fields(array(
      'sid',
      'type',
      'nid',
      'caption',
    ))
    ->execute();
  }

}
#df80aaa3c30f6070f6cf85c15416cfb5
