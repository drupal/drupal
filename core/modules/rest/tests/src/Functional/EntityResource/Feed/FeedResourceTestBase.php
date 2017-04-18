<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Feed;

use Drupal\Tests\rest\Functional\EntityResource\EntityTest\EntityTestResourceTestBase;
use Drupal\aggregator\Entity\Feed;

abstract class FeedResourceTestBase extends EntityTestResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  public static $entityTypeId = 'aggregator_feed';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access news feeds']);
        break;
      case 'POST':
      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['administer news feeds']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity() {
    $feed = Feed::create();
    $feed->set('fid', 1)
      ->set('uuid', 'abcdefg')
      ->setTitle('Feed')
      ->setUrl('http://example.com/rss.xml')
      ->setDescription('Feed Resource Test 1')
      ->setRefreshRate(900)
      ->setLastCheckedTime(123456789)
      ->setQueuedTime(123456789)
      ->setWebsiteUrl('http://example.com')
      ->setImage('http://example.com/feed_logo')
      ->setHash('abcdefg')
      ->setEtag('hijklmn')
      ->setLastModified(123456789)
      ->save();

    return $feed;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'uuid' => [
        [
          'value' => 'abcdefg'
        ]
      ],
      'fid' => [
        [
          'value' => 1
        ]
      ],
      'langcode' => [
        [
          'value' => 'en'
        ]
      ],
      'url' => [
        [
          'value' => 'http://example.com/rss.xml'
        ]
      ],
      'title' => [
        [
          'value' => 'Feed'
        ]
      ],
      'refresh' => [
        [
          'value' => 900
        ]
      ],
      'checked' => [
        [
          'value' => 123456789
        ]
      ],
      'queued' => [
        [
          'value' => 123456789
        ]
      ],
      'link' => [
        [
          'value' => 'http://example.com'
        ]
      ],
      'description' => [
        [
          'value' => 'Feed Resource Test 1'
        ]
      ],
      'image' => [
        [
          'value' => 'http://example.com/feed_logo'
        ]
      ],
      'hash' => [
        [
          'value' => 'abcdefg'
        ]
      ],
      'etag' => [
        [
          'value' => 'hijklmn'
        ]
      ],
      'modified' => [
        [
          'value' => 123456789
        ]
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'title' => [
        [
          'value' => 'Feed Resource Post Test'
        ]
      ],
      'url' => [
        [
          'value' => 'http://example.com/feed'
        ]
      ],
      'refresh' => [
        [
          'value' => 900
        ]
      ],
      'description' => [
        [
          'value' => 'Feed Resource Post Test Description'
        ]
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    switch ($method) {
      case 'GET':
        return "The 'access news feeds' permission is required.";
      case 'POST':
      case 'PATCH':
      case 'DELETE':
        return "The 'administer news feeds' permission is required.";
      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

}
