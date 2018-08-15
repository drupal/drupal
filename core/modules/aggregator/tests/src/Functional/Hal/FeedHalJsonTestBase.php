<?php

namespace Drupal\Tests\aggregator\Functional\Hal;

use Drupal\Tests\aggregator\Functional\Rest\FeedResourceTestBase;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;

abstract class FeedHalJsonTestBase extends FeedResourceTestBase {

  use HalEntityNormalizationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal'];

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

    return $normalization + [
      '_links' => [
        'self' => [
          'href' => $this->baseUrl . '/aggregator/sources/1?_format=hal_json',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/aggregator_feed/aggregator_feed',
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
          'href' => $this->baseUrl . '/rest/type/aggregator_feed/aggregator_feed',
        ],
      ],
    ];
  }

}
