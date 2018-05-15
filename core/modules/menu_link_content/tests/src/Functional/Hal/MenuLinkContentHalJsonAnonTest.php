<?php

namespace Drupal\Tests\menu_link_content\Functional\Hal;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\menu_link_content\Functional\Rest\MenuLinkContentResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class MenuLinkContentHalJsonAnonTest extends MenuLinkContentResourceTestBase {

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
          'href' => $this->baseUrl . '/admin/structure/menu/item/1/edit?_format=hal_json',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/menu_link_content/menu_link_content',
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
          'href' => $this->baseUrl . '/rest/type/menu_link_content/menu_link_content',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    // The 'url.site' cache context is added for '_links' in the response.
    return Cache::mergeTags(parent::getExpectedCacheContexts(), ['url.site']);
  }

}
