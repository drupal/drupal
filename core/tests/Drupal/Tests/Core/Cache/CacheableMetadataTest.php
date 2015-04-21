<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Cache\CacheableMetadataTest.
 */

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Tests\Core\Render\TestCacheableDependency;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Render\Element;

/**
 * @coversDefaultClass \Drupal\Core\Cache\CacheableMetadata
 * @group Cache
 */
class CacheableMetadataTest extends UnitTestCase {

  /**
   * This delegates to Cache::mergeTags(), so just a basic test.
   *
   * @covers ::addCacheTags
   */
  public function testAddCacheTags() {
    $metadata = new CacheableMetadata();
    $add_expected = [
      [ [], [] ],
      [ ['foo:bar'], ['foo:bar'] ],
      [ ['foo:baz'], ['foo:bar', 'foo:baz'] ],
      [ ['axx:first', 'foo:baz'], ['axx:first', 'foo:bar', 'foo:baz'] ],
      [ [], ['axx:first', 'foo:bar', 'foo:baz'] ],
      [ ['axx:first'], ['axx:first', 'foo:bar', 'foo:baz'] ],
    ];

    foreach ($add_expected as $data) {
      list($add, $expected) = $data;
      $metadata->addCacheTags($add);
      $this->assertEquals($expected, $metadata->getCacheTags());
    }
  }

  /**
   * Test valid and invalid values as max age.
   *
   * @covers ::setCacheMaxAge
   * @dataProvider providerSetCacheMaxAge
   */
  public function testSetCacheMaxAge($data, $expect_exception) {
    $metadata = new CacheableMetadata();
    if ($expect_exception) {
      $this->setExpectedException('\InvalidArgumentException');
    }
    $metadata->setCacheMaxAge($data);
    $this->assertEquals($data, $metadata->getCacheMaxAge());
  }

  /**
   * Data provider for testSetCacheMaxAge.
   */
  public function providerSetCacheMaxAge() {
   return [
     [0 , FALSE],
     ['http', TRUE],
     ['0', TRUE],
     [new \stdClass(), TRUE],
     [300, FALSE],
     [[], TRUE],
     [8.0, TRUE]
   ];
  }

  /**
   * @covers ::createFromRenderArray
   * @dataProvider providerTestCreateFromRenderArray
   */
  public function testCreateFromRenderArray(array $render_array, CacheableMetadata $expected) {
    $this->assertEquals($expected, CacheableMetadata::createFromRenderArray($render_array));
  }

  /**
   * Provides test data for createFromRenderArray().
   *
   * @return array
   */
  public function providerTestCreateFromRenderArray() {
    $data = [];

    $empty_metadata = new CacheableMetadata();
    $nonempty_metadata = new CacheableMetadata();
    $nonempty_metadata->setCacheContexts(['qux'])
      ->setCacheTags(['foo:bar']);

    $empty_render_array = [];
    $nonempty_render_array = [
      '#cache' => [
        'contexts' => ['qux'],
        'tags' => ['foo:bar'],
        'max-age' => Cache::PERMANENT,
      ],
    ];

    $data[] = [$empty_render_array, $empty_metadata];
    $data[] = [$nonempty_render_array, $nonempty_metadata];

    return $data;
  }

  /**
   * @covers ::createFromObject
   * @dataProvider providerTestCreateFromObject
   */
  public function testCreateFromObject($object, CacheableMetadata $expected) {
    $this->assertEquals($expected, CacheableMetadata::createFromObject($object));
  }

  /**
   * Provides test data for createFromObject().
   *
   * @return array
   */
  public function providerTestCreateFromObject() {
    $data = [];

    $empty_metadata = new CacheableMetadata();
    $nonempty_metadata = new CacheableMetadata();
    $nonempty_metadata->setCacheContexts(['qux'])
      ->setCacheTags(['foo:bar'])
      ->setCacheMaxAge(600);
    $uncacheable_metadata = new CacheableMetadata();
    $uncacheable_metadata->setCacheMaxAge(0);

    $empty_cacheable_object = new TestCacheableDependency([], [], Cache::PERMANENT);
    $nonempty_cacheable_object = new TestCacheableDependency(['qux'], ['foo:bar'], 600);
    $uncacheable_object = new \stdClass();

    $data[] = [$empty_cacheable_object, $empty_metadata];
    $data[] = [$nonempty_cacheable_object, $nonempty_metadata];
    $data[] = [$uncacheable_object, $uncacheable_metadata];

    return $data;
  }

}
