<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Cache\CacheableMetadataTest.
 */

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\CacheableMetadata;
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
}
