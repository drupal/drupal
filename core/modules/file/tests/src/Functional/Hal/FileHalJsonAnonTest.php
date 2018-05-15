<?php

namespace Drupal\Tests\file\Functional\Hal;

use Drupal\Core\Cache\Cache;
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

    $url = file_create_url($this->entity->getFileUri());
    // @see \Drupal\Tests\hal\Functional\EntityResource\File\FileHalJsonAnonTest::testGetBcUriField()
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
  protected function getExpectedCacheTags() {
    return Cache::mergeTags(parent::getExpectedCacheTags(), ['config:hal.settings']);
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

  /**
   * @see hal_update_8501()
   */
  public function testGetBcUriField() {
    $this->config('hal.settings')->set('bc_file_uri_as_url_normalizer', TRUE)->save(TRUE);

    $this->initAuthentication();
    $url = $this->getEntityResourceUrl();
    $url->setOption('query', ['_format' => static::$format]);
    $request_options = $this->getAuthenticationRequestOptions('GET');
    $this->provisionEntityResource();
    $this->setUpAuthorization('GET');
    $response = $this->request('GET', $url, $request_options);
    $expected = $this->getExpectedNormalizedEntity();
    static::recursiveKSort($expected);
    $actual = $this->serializer->decode((string) $response->getBody(), static::$format);
    static::recursiveKSort($actual);
    $this->assertSame($expected, $actual);

    // Explicitly assert that $file->uri->value is an absolute file URL, unlike
    // the default normalization.
    $this->assertSame($this->baseUrl . '/' . $this->siteDirectory . '/files/drupal.txt', $actual['uri'][0]['value']);
  }

  /**
   * {@inheritdoc}
   */
  public function testPatch() {
    // @todo https://www.drupal.org/node/1927648
    $this->markTestSkipped();
  }

}
