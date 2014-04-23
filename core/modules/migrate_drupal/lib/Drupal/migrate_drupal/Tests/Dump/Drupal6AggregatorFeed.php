<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Feed.
 */

namespace Drupal\migrate_drupal\Tests\Dump;
/**
 * Database dump for testing feed migration.
 */
class Drupal6AggregatorFeed extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('aggregator_feed', array(
      'description' => 'Stores feeds to be parsed by the aggregator.',
      'fields' => array(
        'fid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique feed ID.',
        ),
        'title' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Title of the feed.',
        ),
        'url' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'URL to the feed.',
        ),
        'refresh' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'How often to check for new feed items, in seconds.',
        ),
        'checked' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Last time feed was checked for new items, as Unix timestamp.',
        ),
        'link' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The parent website of the feed; comes from the &lt;link&gt; element in the feed.',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
          'description' => "The parent website's description; comes from the &lt;description&gt; element in the feed.",
        ),
        'image' => array(
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'An image representing the feed.',
        ),
        'etag' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Entity tag HTTP response header, used for validating cache.',
        ),
        'modified' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'When the feed was last modified, as a Unix timestamp.',
        ),
        'block' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => "Number of items to display in the feed's block.",
        )
      ),
      'primary key' => array('fid'),
      'unique keys' => array(
        'url'  => array('url'),
        'title' => array('title'),
      ),
    ));

    $this->database->insert('aggregator_feed')->fields(array(
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
      'fid' => 5,
      'title' => 'Know Your Meme',
      'url' => 'http://knowyourmeme.com/newsfeed.rss',
      'refresh' => 900,
      'checked' => 1387659487,
      'link' => 'http://knowyourmeme.com',
      'description' => 'New items added to the News Feed',
      'image' => 'http://b.thumbs.redditmedia.com/harEHsUUZVajabtC.png',
      'etag' => '"213cc1365b96c310e92053c5551f0504"',
      'modified' => 0,
      'block' => 5,
    ))
    ->execute();
    $this->setModuleVersion('aggregator', 6001);
  }

}
