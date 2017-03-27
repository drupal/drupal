<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityTestLabel;

use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityTestLabel\EntityTestLabelResourceTestBase;
use Drupal\user\Entity\User;

/**
 * @group hal
 */
class EntityTestLabelHalJsonAnonTest extends EntityTestLabelResourceTestBase {

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

    $author = User::load(0);
    return $normalization + [
      '_links' => [
        'self' => [
          'href' => '',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/entity_test_label/entity_test_label',
        ],
        $this->baseUrl . '/rest/relation/entity_test_label/entity_test_label/user_id' => [
          [
            'href' => $this->baseUrl . '/user/0?_format=hal_json',
            'lang' => 'en',
          ],
        ],
      ],
      '_embedded' => [
        $this->baseUrl . '/rest/relation/entity_test_label/entity_test_label/user_id' => [
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/user/0?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/user/user',
              ],
            ],
            'uuid' => [
              [
                'value' => $author->uuid(),
              ],
            ],
            'lang' => 'en',
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
          'href' => $this->baseUrl . '/rest/type/entity_test_label/entity_test_label',
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
