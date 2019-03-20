<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\aggregator\Entity\Feed;
use Drupal\Core\Url;
use Drupal\Tests\jsonapi\Traits\CommonCollectionFilterAccessTestPatternsTrait;

/**
 * JSON:API integration test for the "Feed" content entity type.
 *
 * @group jsonapi
 */
class FeedTest extends ResourceTestBase {

  use CommonCollectionFilterAccessTestPatternsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'aggregator_feed';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'aggregator_feed--aggregator_feed';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\config_test\ConfigTestInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * {@inheritdoc}
   */
  protected static $uniqueFieldNames = ['url'];

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
  protected function createAnotherEntity($key) {
    /* @var \Drupal\aggregator\FeedInterface $duplicate */
    $duplicate = $this->getEntityDuplicate($this->entity, $key);
    $duplicate->set('field_rest_test', 'Duplicate feed entity');
    $duplicate->setUrl("http://example.com/$key.xml");
    $duplicate->save();
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/aggregator_feed/aggregator_feed/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'aggregator_feed--aggregator_feed',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'url' => 'http://example.com/rss.xml',
          'title' => 'Feed',
          'refresh' => 900,
          'checked' => '1973-11-29T21:33:09+00:00',
          'queued' => '1973-11-29T21:33:09+00:00',
          'link' => 'http://example.com',
          'description' => 'Feed Resource Test 1',
          'image' => 'http://example.com/feed_logo',
          'hash' => 'abcdefg',
          'etag' => 'hijklmn',
          'modified' => '1973-11-29T21:33:09+00:00',
          'langcode' => 'en',
          'drupal_internal__fid' => 1,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'aggregator_feed--aggregator_feed',
        'attributes' => [
          'title' => 'Feed Resource Post Test',
          'url' => 'http://example.com/feed',
          'refresh' => 900,
          'description' => 'Feed Resource Post Test Description',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The 'access news feeds' permission is required.";

      case 'POST':
      case 'PATCH':
      case 'DELETE':
        return "The 'administer news feeds' permission is required.";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testCollectionFilterAccess() {
    $this->doTestCollectionFilterAccessBasedOnPermissions('title', 'access news feeds');
  }

}
