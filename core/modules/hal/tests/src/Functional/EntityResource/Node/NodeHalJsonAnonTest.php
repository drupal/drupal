<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Node;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\Node\NodeResourceTestBase;
use Drupal\user\Entity\User;

/**
 * @group hal
 */
class NodeHalJsonAnonTest extends NodeResourceTestBase {

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
  protected static $expectedErrorMimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'created',
    'changed',
    'promote',
    'sticky',
    'revision_timestamp',
    'revision_uid',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $default_normalization = parent::getExpectedNormalizedEntity();

    $normalization = $this->applyHalFieldNormalization($default_normalization);

    $author = User::load($this->entity->getOwnerId());
    return  $normalization + [
      '_links' => [
        'self' => [
          'href' => $this->baseUrl . '/node/1?_format=hal_json',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/node/camelids',
        ],
        $this->baseUrl . '/rest/relation/node/camelids/uid' => [
          [
            'href' => $this->baseUrl . '/user/' . $author->id() . '?_format=hal_json',
            'lang' => 'en',
          ],
        ],
        $this->baseUrl . '/rest/relation/node/camelids/revision_uid' => [
          [
            'href' => $this->baseUrl . '/user/' . $author->id() . '?_format=hal_json',
          ],
        ],
      ],
      '_embedded' => [
        $this->baseUrl . '/rest/relation/node/camelids/uid' => [
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/user/' . $author->id() . '?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/user/user',
              ],
            ],
            'uuid' => [
              ['value' => $author->uuid()]
            ],
            'lang' => 'en',
          ],
        ],
        $this->baseUrl . '/rest/relation/node/camelids/revision_uid' => [
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/user/' . $author->id() . '?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/user/user',
              ],
            ],
            'uuid' => [
              ['value' => $author->uuid()]
            ],
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
          'href' => $this->baseUrl . '/rest/type/node/camelids',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    // The 'url.site' cache context is added for '_links' in the response.
    return Cache::mergeContexts(parent::getExpectedCacheContexts(), ['url.site']);
  }

}
