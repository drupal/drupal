<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\AggregatorFeed.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the aggregator_feed table.
 */
class AggregatorFeed extends Drupal6DumpBase {

  public function load() {
    $this->createTable("aggregator_feed", array(
      'primary key' => array(
        'fid',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'url' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'refresh' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'checked' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'link' => array(
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
        'image' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'etag' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'modified' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'block' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("aggregator_feed")->fields(array(
      'fid',
      'title',
      'url',
      'refresh',
      'checked',
      'link',
      'description',
      'image',
      'etag',
      'modified',
      'block',
    ))
    ->values(array(
      'fid' => '5',
      'title' => 'Know Your Meme',
      'url' => 'http://knowyourmeme.com/newsfeed.rss',
      'refresh' => '900',
      'checked' => '1387659487',
      'link' => 'http://knowyourmeme.com',
      'description' => 'New items added to the News Feed',
      'image' => 'http://b.thumbs.redditmedia.com/harEHsUUZVajabtC.png',
      'etag' => '"213cc1365b96c310e92053c5551f0504"',
      'modified' => '0',
      'block' => '5',
    ))->execute();
  }

}
