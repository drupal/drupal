<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Media;

<<<<<<< HEAD
use Drupal\Core\Cache\Cache;
=======
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
use Drupal\file\Entity\File;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\Media\MediaResourceTestBase;
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
<<<<<<< HEAD
        $this->baseUrl . '/rest/relation/media/camelids/field_media_file' => [
=======
        $this->baseUrl . '/rest/relation/media/camelids/field_media_file_1' => [
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
          [
            'href' => $file->url(),
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
            'href' => $thumbnail->url(),
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
<<<<<<< HEAD
        $this->baseUrl . '/rest/relation/media/camelids/field_media_file' => [
=======
        $this->baseUrl . '/rest/relation/media/camelids/field_media_file_1' => [
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
          [
            '_links' => [
              'self' => [
                'href' => $file->url(),
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/file/file',
              ],
            ],
            'lang' => 'en',
<<<<<<< HEAD
=======
            'uri' => [
              [
                'value' => $file->url(),
              ],
            ],
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
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
                'href' => $thumbnail->url(),
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/file/file',
              ],
            ],
            'lang' => 'en',
<<<<<<< HEAD
=======
            'uri' => [
              [
                'value' => $thumbnail->url(),
              ],
            ],
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
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
  protected function getNormalizedPostEntity() {
    return parent::getNormalizedPostEntity() + [
      '_links' => [
        'type' => [
          'href' => $this->baseUrl . '/rest/type/media/camelids',
        ],
      ],
    ];
  }

<<<<<<< HEAD
  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags() {
    return Cache::mergeTags(parent::getExpectedCacheTags(), ['config:hal.settings']);
  }

=======
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
}
