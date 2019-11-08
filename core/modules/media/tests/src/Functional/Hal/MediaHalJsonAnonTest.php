<?php

namespace Drupal\Tests\media\Functional\Hal;

use Drupal\Core\Cache\Cache;
use Drupal\file\Entity\File;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\media\Functional\Rest\MediaResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\user\Entity\User;

/**
 * @group hal
 */
class MediaHalJsonAnonTest extends MediaResourceTestBase {

  use HalEntityNormalizationTrait;
  use AnonResourceTestTrait;

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

    $file = File::load(1);
    $thumbnail = File::load(2);
    $author = User::load($this->entity->getOwnerId());
    return $normalization + [
      '_links' => [
        'self' => [
          'href' => $this->baseUrl . '/media/1?_format=hal_json',
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/media/camelids',
        ],
        $this->baseUrl . '/rest/relation/media/camelids/field_media_file' => [
          [
            'href' => $file->createFileUrl(FALSE),
            'lang' => 'en',
          ],
        ],
        $this->baseUrl . '/rest/relation/media/camelids/revision_user' => [
          [
            'href' => $this->baseUrl . '/user/' . $author->id() . '?_format=hal_json',
          ],
        ],
        $this->baseUrl . '/rest/relation/media/camelids/thumbnail' => [
          [
            'href' => $thumbnail->createFileUrl(FALSE),
            'lang' => 'en',
          ],
        ],
        $this->baseUrl . '/rest/relation/media/camelids/uid' => [
          [
            'href' => $this->baseUrl . '/user/' . $author->id() . '?_format=hal_json',
            'lang' => 'en',
          ],
        ],
      ],
      '_embedded' => [
        $this->baseUrl . '/rest/relation/media/camelids/field_media_file' => [
          [
            '_links' => [
              'self' => [
                'href' => $file->createFileUrl(FALSE),
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/file/file',
              ],
            ],
            'lang' => 'en',
            'uuid' => [
              [
                'value' => $file->uuid(),
              ],
            ],
          ],
        ],
        $this->baseUrl . '/rest/relation/media/camelids/revision_user' => [
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
              [
                'value' => $author->uuid(),
              ],
            ],
          ],
        ],
        $this->baseUrl . '/rest/relation/media/camelids/thumbnail' => [
          [
            '_links' => [
              'self' => [
                'href' => $thumbnail->createFileUrl(FALSE),
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/file/file',
              ],
            ],
            'lang' => 'en',
            'uuid' => [
              [
                'value' => $thumbnail->uuid(),
              ],
            ],
          ],
        ],
        $this->baseUrl . '/rest/relation/media/camelids/uid' => [
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
  protected function getExpectedNormalizedFileEntity() {
    $normalization = parent::getExpectedNormalizedFileEntity();

    $owner = static::$auth ? $this->account : User::load(0);

    // Cannot use applyHalFieldNormalization() as it uses the $entity property
    // from the test class, which in the case of file upload tests, is the
    // parent entity test entity for the file that's created.

    // The HAL normalization adds entity reference fields to '_links' and
    // '_embedded'.
    unset($normalization['uid']);

    return $normalization + [
      '_links' => [
        'self' => [
          // @todo This can use a proper link once
          // https://www.drupal.org/project/drupal/issues/2907402 is complete.
          // This link matches what is generated from from File::url(), a
          // resource URL is currently not available.
          'href' => file_create_url($normalization['uri'][0]['value']),
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/file/file',
        ],
        $this->baseUrl . '/rest/relation/file/file/uid' => [
          ['href' => $this->baseUrl . '/user/' . $owner->id() . '?_format=hal_json'],
        ],
      ],
      '_embedded' => [
        $this->baseUrl . '/rest/relation/file/file/uid' => [
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . '/user/' . $owner->id() . '?_format=hal_json',
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/user/user',
              ],
            ],
            'uuid' => [
              [
                'value' => $owner->uuid(),
              ],
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
          'href' => $this->baseUrl . '/rest/type/media/camelids',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags() {
    return Cache::mergeTags(parent::getExpectedCacheTags(), ['config:hal.settings']);
  }

}
