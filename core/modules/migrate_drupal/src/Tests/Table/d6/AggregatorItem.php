<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\AggregatorItem.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

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
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
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
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
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
    ->values(array(
      'iid' => '1',
      'fid' => '5',
      'title' => 'This (three) weeks in Drupal Core - January 10th 2014',
      'link' => 'https://groups.drupal.org/node/395218',
      'author' => 'larowlan',
      'description' => "<h2 id='new'>What's new with Drupal 8?</h2>",
      'timestamp' => '1389297196',
      'guid' => '395218 at https://groups.drupal.org',
    ))->execute();
  }

}
