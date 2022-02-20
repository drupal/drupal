<?php

namespace Drupal\Tests\hal\Functional\file;

use Drupal\Tests\file\Functional\Rest\FileResourceTestBase;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class FileHalJsonAnonTest extends FileResourceTestBase {

  use HalEntityNormalizationTrait;
  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal'];

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

    $url = $this->entity->createFileUrl(FALSE);
    if ($this->config('hal.settings')->get('bc_file_uri_as_url_normalizer')) {
      $normalization['uri'][0]['value'] = $url;
    }

    $uid = $this->author->id();

    return $normalization + [
      '_embedded' => [
        $this->baseUrl . '/rest/relation/file/file/uid' => [
          [
            '_links' => [
              'self' => [
                'href' => $this->baseUrl . "/user/$uid?_format=hal_json",
              ],
              'type' => [
                'href' => $this->baseUrl . '/rest/type/user/user',
              ],
            ],
            'uuid' => [
              [
                'value' => $this->author->uuid(),
              ],
            ],
          ],
        ],
      ],
      '_links' => [
        'self' => [
          'href' => $url,
        ],
        'type' => [
          'href' => $this->baseUrl . '/rest/type/file/file',
        ],
        $this->baseUrl . '/rest/relation/file/file/uid' => [
          [
            'href' => $this->baseUrl . "/user/$uid?_format=hal_json",
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
          'href' => $this->baseUrl . '/rest/type/file/file',
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
