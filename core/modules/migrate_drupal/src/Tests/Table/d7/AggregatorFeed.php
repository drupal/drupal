<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\AggregatorFeed.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the aggregator_feed table.
 */
class AggregatorFeed extends DrupalDumpBase {

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
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
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
        'queued' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'link' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
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
        'hash' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
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
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("aggregator_feed")->fields(array(
      'fid',
      'title',
      'url',
      'refresh',
      'checked',
      'queued',
      'link',
      'description',
      'image',
      'hash',
      'etag',
      'modified',
      'block',
    ))
    ->values(array(
      'fid' => '1',
      'title' => 'Know Your Meme',
      'url' => 'http://knowyourmeme.com/newsfeed.rss',
      'refresh' => '900',
      'checked' => '1387659487',
      'queued' => '0',
      'link' => 'http://knowyourmeme.com',
      'description' => 'New items added to the News Feed',
      'image' => 'http://b.thumbs.redditmedia.com/harEHsUUZVajabtC.png',
      'hash' => '1c1e3b6c10ce02f226882aca11709051bba61df2b8eac17ceec5bf74048f1954',
      'etag' => '"213cc1365b96c310e92053c5551f0504"',
      'modified' => '0',
      'block' => '5',
    ))->execute();
  }

}
#82e906f4d10bd7d1d4bab524e946df94
