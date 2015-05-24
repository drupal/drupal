<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\AggregatorItem.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the aggregator_item table.
 */
class AggregatorItem extends DrupalDumpBase {

  public function load() {
    $this->createTable("aggregator_item", array(
      'primary key' => array(
        'iid',
      ),
      'fields' => array(
        'iid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'fid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'link' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'author' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
        'guid' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("aggregator_item")->fields(array(
      'iid',
      'fid',
      'title',
      'link',
      'author',
      'description',
      'timestamp',
      'guid',
    ))
    ->execute();
  }

}
#e92145ca6ce34fac4983db361aba0632
