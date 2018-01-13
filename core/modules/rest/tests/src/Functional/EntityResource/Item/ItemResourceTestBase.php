<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Item;

use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * ResourceTestBase for Item entity.
 */
abstract class ItemResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'aggregator_item';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * The Item entity.
   *
   * @var \Drupal\aggregator\ItemInterface
   */
  protected $entity;

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
  protected function createEntity() {
    // Create a "Camelids" feed.
    $feed = Feed::create([
      'title' => 'Camelids',
      'url' => 'https://groups.drupal.org/not_used/167169',
      'refresh' => 900,
      'checked' => 1389919932,
      'description' => 'Drupal Core Group feed',
    ]);
    $feed->save();

    // Create a "Llama" item.
    $item = Item::create();
    $item->setTitle('Llama')
      ->setFeedId($feed->id())
      ->setLink('https://www.drupal.org/')
      ->setPostedTime(123456789)
      ->save();

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity() {
    $entity = $this->entity->createDuplicate();
    $entity->setLink('https://www.example.org/');
    $label_key = $entity->getEntityType()->getKey('label');
    if ($label_key) {
      $entity->set($label_key, $entity->label() . '_dupe');
    }
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $feed = Feed::load($this->entity->getFeedId());

    return [
      'iid' => [
        [
          'value' => 1,
        ],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'fid' => [
        [
          'target_id' => 1,
          'target_type' => 'aggregator_feed',
          'target_uuid' => $feed->uuid(),
          'url' => base_path() . 'aggregator/sources/1',
        ],
      ],
      'title' => [
        [
          'value' => 'Llama',
        ],
      ],
      'link' => [
        [
          'value' => 'https://www.drupal.org/',
        ],
      ],
      'author' => [],
      'description' => [],
      'timestamp' => [
        $this->formatExpectedTimestampItemValues(123456789),
      ],
      'guid' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'fid' => [
        [
          'target_id' => 1,
        ],
      ],
      'title' => [
        [
          'value' => 'Llama',
        ],
      ],
      'link' => [
        [
          'value' => 'https://www.drupal.org/',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    // @see ::createEntity()
    return ['user.permissions'];
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
