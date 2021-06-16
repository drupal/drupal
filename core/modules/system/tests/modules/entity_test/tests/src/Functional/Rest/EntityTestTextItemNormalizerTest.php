<?php

namespace Drupal\Tests\entity_test\Functional\Rest;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class EntityTestTextItemNormalizerTest extends EntityTestResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    parent::setUpAuthorization($method);
    if (in_array($method, ['POST', 'PATCH'], TRUE)) {
      $this->grantPermissionsToTestedRole(['use text format my_text_format']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $expected = parent::getExpectedNormalizedEntity();
    $expected['field_test_text'] = [
      [
        'value' => 'Cádiz is the oldest continuously inhabited city in Spain and a nice place to spend a Sunday with friends.',
        'format' => 'my_text_format',
        'processed' => '<p>Cádiz is the oldest continuously inhabited city in Spain and a nice place to spend a Sunday with friends.</p>' . "\n" . '<p>This is a dynamic llama.</p>',
      ],
    ];
    return $expected;
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity = parent::createEntity();
    if (!FilterFormat::load('my_text_format')) {
      FilterFormat::create([
        'format' => 'my_text_format',
        'name' => 'My Text Format',
        'filters' => [
          'filter_test_assets' => [
            'weight' => -1,
            'status' => TRUE,
          ],
          'filter_test_cache_tags' => [
            'weight' => 0,
            'status' => TRUE,
          ],
          'filter_test_cache_contexts' => [
            'weight' => 0,
            'status' => TRUE,
          ],
          'filter_test_cache_merge' => [
            'weight' => 0,
            'status' => TRUE,
          ],
          'filter_test_placeholders' => [
            'weight' => 1,
            'status' => TRUE,
          ],
          'filter_autop' => [
            'status' => TRUE,
          ],
        ],
      ])->save();
    }
    $entity->field_test_text = [
      'value' => 'Cádiz is the oldest continuously inhabited city in Spain and a nice place to spend a Sunday with friends.',
      'format' => 'my_text_format',
    ];
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    $post_entity = parent::getNormalizedPostEntity();
    $post_entity['field_test_text'] = [
      [
        'value' => 'Llamas are awesome.',
        'format' => 'my_text_format',
      ],
    ];
    return $post_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags() {
    return Cache::mergeTags([
      // The cache tag set by the processed_text element itself.
      'config:filter.format.my_text_format',
      // The cache tags set by the filter_test_cache_tags filter.
      'foo:bar',
      'foo:baz',
      // The cache tags set by the filter_test_cache_merge filter.
      'merge:tag',
    ], parent::getExpectedCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return Cache::mergeContexts([
      // The cache context set by the filter_test_cache_contexts filter.
      'languages:' . LanguageInterface::TYPE_CONTENT,
      // The default cache contexts for Renderer.
      'languages:' . LanguageInterface::TYPE_INTERFACE,
      'theme',
      // The cache tags set by the filter_test_cache_merge filter.
      'user.permissions',
    ], parent::getExpectedCacheContexts());
  }

  /**
   * Tests GETting an entity with the test text field set to a specific format.
   *
   * @dataProvider providerTestGetWithFormat
   */
  public function testGetWithFormat($text_format_id, array $expected_cache_tags) {
    FilterFormat::create([
      'name' => 'Pablo Picasso',
      'format' => 'pablo',
      'langcode' => 'es',
      'filters' => [],
    ])->save();

    // Set TextItemBase field's value for testing, using the given text format.
    $value = [
      'value' => $this->randomString(),
    ];
    if ($text_format_id !== FALSE) {
      $value['format'] = $text_format_id;
    }
    $this->entity->set('field_test_text', $value)->save();

    $this->initAuthentication();
    $url = $this->getEntityResourceUrl();
    $url->setOption('query', ['_format' => static::$format]);
    $request_options = $this->getAuthenticationRequestOptions('GET');
    $this->provisionEntityResource();
    $this->setUpAuthorization('GET');
    $response = $this->request('GET', $url, $request_options);
    $expected_cache_tags = Cache::mergeTags($expected_cache_tags, parent::getExpectedCacheTags());
    $this->assertSame($expected_cache_tags, explode(' ', $response->getHeader('X-Drupal-Cache-Tags')[0]));
  }

  public function providerTestGetWithFormat() {
    return [
      'format specified (different from fallback format)' => [
        'pablo',
        ['config:filter.format.pablo'],
      ],
      'format specified (happens to be the same as fallback format)' => [
        'plain_text',
        ['config:filter.format.plain_text'],
      ],
      'no format specified: fallback format used automatically' => [
        FALSE,
        ['config:filter.format.plain_text', 'config:filter.settings'],
      ],
    ];
  }

}
