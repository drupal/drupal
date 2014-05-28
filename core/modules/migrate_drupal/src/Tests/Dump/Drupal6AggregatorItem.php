<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6AggregatorItem.
 */

namespace Drupal\migrate_drupal\Tests\Dump;
/**
 * Database dump for testing aggregator item migration.
 */
class Drupal6AggregatorItem extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('aggregator_item', array(
      'description' => 'Stores the individual items imported from feeds.',
      'fields' => array(
        'iid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique ID for feed item.',
        ),
        'fid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {aggregator_feed}.fid to which this item belongs.',
        ),
        'title' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Title of the feed item.',
        ),
        'link' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Link to the feed item.',
        ),
        'author' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Author of the feed item.',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'Body of the feed item.',
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => FALSE,
          'description' => 'Post date of feed item, as a Unix timestamp.',
        ),
        'guid' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'description' => 'Unique identifier for the feed item.',
        ),
      ),
      'primary key' => array('iid'),
      'indexes' => array('fid' => array('fid')),
    ));

    $this->database->insert('aggregator_item')->fields(array(
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
      'iid' => 1,
      'fid' => 5,
      'title' => 'This (three) weeks in Drupal Core - January 10th 2014',
      'link' => 'https://groups.drupal.org/node/395218',
      'author' => 'larowlan',
      'description' => "<h2 id='new'>What's new with Drupal 8?</h2>",
      'timestamp' => 1389297196,
      'guid' => '395218 at https://groups.drupal.org',
    ))
    ->execute();

    $this->setModuleVersion('aggregator', '6001');
  }

}
