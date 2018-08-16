<?php

namespace Drupal\Tests\aggregator\Functional\Hal;

use Drupal\aggregator\Entity\Feed;
use Drupal\Tests\aggregator\Functional\Rest\ItemResourceTestBase;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * ResourceTestBase for Item entity.
 */
abstract class ItemHalJsonTestBase extends ItemResourceTestBase {

  use HalEntityNormalizationTrait;
  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $default_normalization = parent::getExpectedNormalizedEntity();
    $normalization = $this->applyHalFieldNormalization($default_normalization);
    $feed = Feed::load($this->entity->getFeedId());

    return $normalization + [
      '_embedded' => [
        $this->baseUrl . '/rest/relation/aggregator_item/aggregator_item/fid' => [
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/aggregator/sources/1?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/aggregator_feed/aggregator_feed',
              ],
            ],
            'uuid' => [
              [
                'value' => $feed->uuid(),
              ],
            ],
          ],
        ],
      ],
      '_links' => [
        'self' => [
          'href' => '',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/aggregator_item/aggregator_item',
        ],
        $this->baseUrl . '/rest/relation/aggregator_item/aggregator_item/fid' => [
          [
            'href' => $this->baseUrl . '/aggregator/sources/' . $feed->id() . '?_format=hal_json',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return parent::getNormalizedPostEntity() + [
      '_links' => [
        'type' => [
          'href' => $this->baseUrl . '/rest/type/aggregator_item/aggregator_item',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return [
      'url.site',
      'user.permissions',
    ];
  }

}
