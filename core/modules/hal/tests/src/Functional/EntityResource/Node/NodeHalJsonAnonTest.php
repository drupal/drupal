<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Node;

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
  protected static $patchProtectedFieldNames = [
    'revision_timestamp',
    'created',
    'changed',
    'promote',
    'sticky',
    'path',
    'revision_uid',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $default_normalization = parent::getExpectedNormalizedEntity();

    $normalization = $this->applyHalFieldNormalization($default_normalization);

    $author = User::load($this->entity->getOwnerId());
    return $normalization + [
      '_links' => [
        'self' => [
          'href' => $this->baseUrl . '/llama?_format=hal_json',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/node/camelids',
        ],
        $this->baseUrl . '/rest/relation/node/camelids/revision_uid' => [
          [
            'href' => $this->baseUrl . '/user/' . $author->id() . '?_format=hal_json',
          ],
        ],
        $this->baseUrl . '/rest/relation/node/camelids/uid' => [
          [
            'href' => $this->baseUrl . '/user/' . $author->id() . '?_format=hal_json',
            'lang' => 'en',
          ],
        ],
      ],
      '_embedded' => [
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

}
