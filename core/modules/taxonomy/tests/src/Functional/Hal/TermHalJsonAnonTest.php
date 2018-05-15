<?php

namespace Drupal\Tests\taxonomy\Functional\Hal;

use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\taxonomy\Functional\Rest\TermResourceTestBase;

/**
 * @group hal
 */
class TermHalJsonAnonTest extends TermResourceTestBase {

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

    return $normalization + [
      '_links' => [
        'self' => [
          'href' => $this->baseUrl . '/llama?_format=hal_json',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/taxonomy_term/camelids',
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
          'href' => $this->baseUrl . '/rest/type/taxonomy_term/camelids',
        ],
      ],
    ];
  }

}
