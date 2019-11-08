<?php

namespace Drupal\Tests\entity_test\Functional\Hal;

use Drupal\Tests\entity_test\Functional\Rest\EntityTestResourceTestBase;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\FormatSpecificGetBcRouteTestTrait;
use Drupal\user\Entity\User;

/**
 * @group hal
 */
class EntityTestHalJsonAnonTest extends EntityTestResourceTestBase {

  use HalEntityNormalizationTrait;
  use AnonResourceTestTrait;
  use FormatSpecificGetBcRouteTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
          'href' => $this->baseUrl . '/entity_test/1?_format=hal_json',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/entity_test/entity_test',
        ],
        $this->baseUrl . '/rest/relation/entity_test/entity_test/user_id' => [
          [
            'href' => $this->baseUrl . '/user/0?_format=hal_json',
            'lang' => 'en',
          ],
        ],
      ],
      '_embedded' => [
        $this->baseUrl . '/rest/relation/entity_test/entity_test/user_id' => [
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
              ['value' => $author->uuid()],
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
          'href' => $this->baseUrl . '/rest/type/entity_test/entity_test',
        ],
      ],
    ];
  }

}
