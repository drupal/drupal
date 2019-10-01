<?php

namespace Drupal\FunctionalTests\Hal;

use Drupal\FunctionalTests\Rest\PathAliasResourceTestBase;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;

/**
 * Base hal_json test class for the path_alias entity type.
 */
abstract class PathAliasHalJsonTestBase extends PathAliasResourceTestBase {

  use HalEntityNormalizationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $default_normalization = parent::getExpectedNormalizedEntity();
    $normalization = $this->applyHalFieldNormalization($default_normalization);
    return $normalization + [
      '_links' => [
        'self' => [
          'href' => '',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/path_alias/path_alias',
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
          'href' => $this->baseUrl . '/rest/type/path_alias/path_alias',
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
