<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\BubbleableMetadataTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Render\Element;

/**
 * @coversDefaultClass \Drupal\Core\Render\BubbleableMetadata
 * @group Render
 */
class BubbleableMetadataTest extends UnitTestCase {

  /**
   * @covers ::applyTo
   * @dataProvider providerTestApplyTo
   */
  public function testApplyTo(BubbleableMetadata $metadata, array $render_array, array $expected) {
    $this->assertNull($metadata->applyTo($render_array));
    $this->assertEquals($expected, $render_array);
  }

  /**
   * Provides test data for apply().
   *
   * @return array
   */
  public function providerTestApplyTo() {
    $data = [];

    $empty_metadata = new BubbleableMetadata();
    $nonempty_metadata = new BubbleableMetadata();
    $nonempty_metadata->setCacheContexts(['qux'])
      ->setCacheTags(['foo:bar'])
      ->setAssets(['settings' => ['foo' => 'bar']]);

    $empty_render_array = [];
    $nonempty_render_array = [
      '#cache' => [
        'contexts' => ['qux'],
        'tags' => ['llamas:are:awesome:but:kittens:too'],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => [
        'library' => [
          'core/jquery',
        ],
      ],
      '#post_render_cache' => [],
    ];


    $expected_when_empty_metadata = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => [],
      '#post_render_cache' => [],
    ];
    $data[] = [$empty_metadata, $empty_render_array, $expected_when_empty_metadata];
    $data[] = [$empty_metadata, $nonempty_render_array, $expected_when_empty_metadata];
    $expected_when_nonempty_metadata = [
      '#cache' => [
        'contexts' => ['qux'],
        'tags' => ['foo:bar'],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => [
        'settings' => [
          'foo' => 'bar',
        ],
      ],
      '#post_render_cache' => [],
    ];
    $data[] = [$nonempty_metadata, $empty_render_array, $expected_when_nonempty_metadata];
    $data[] = [$nonempty_metadata, $nonempty_render_array, $expected_when_nonempty_metadata];

    return $data;
  }

  /**
   * @covers ::createFromRenderArray
   * @dataProvider providerTestCreateFromRenderArray
   */
  public function testCreateFromRenderArray(array $render_array, BubbleableMetadata $expected) {
    $this->assertEquals($expected, BubbleableMetadata::createFromRenderArray($render_array));
  }

  /**
   * Provides test data for createFromRenderArray().
   *
   * @return array
   */
  public function providerTestCreateFromRenderArray() {
    $data = [];

    $empty_metadata = new BubbleableMetadata();
    $nonempty_metadata = new BubbleableMetadata();
    $nonempty_metadata->setCacheContexts(['qux'])
      ->setCacheTags(['foo:bar'])
      ->setAssets(['settings' => ['foo' => 'bar']]);

    $empty_render_array = [];
    $nonempty_render_array = [
      '#cache' => [
        'contexts' => ['qux'],
        'tags' => ['foo:bar'],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => [
        'settings' => [
          'foo' => 'bar',
        ],
      ],
      '#post_render_cache' => [],
    ];


    $data[] = [$empty_render_array, $empty_metadata];
    $data[] = [$nonempty_render_array, $nonempty_metadata];

    return $data;
  }

  /**
   * @covers ::createFromObject
   * @dataProvider providerTestCreateFromObject
   */
  public function testCreateFromObject(CacheableDependencyInterface $object, BubbleableMetadata $expected) {
    $this->assertEquals($expected, BubbleableMetadata::createFromObject($object));
  }

  /**
   * Provides test data for createFromObject().
   *
   * @return array
   */
  public function providerTestCreateFromObject() {
    $data = [];

    $empty_metadata = new BubbleableMetadata();
    $nonempty_metadata = new BubbleableMetadata();
    $nonempty_metadata->setCacheContexts(['qux'])
      ->setCacheTags(['foo:bar'])
      ->setCacheMaxAge(600);

    $empty_cacheable_object = new TestCacheableDependency([], [], Cache::PERMANENT);
    $nonempty_cacheable_object = new TestCacheableDependency(['qux'], ['foo:bar'], 600);

    $data[] = [$empty_cacheable_object, $empty_metadata];
    $data[] = [$nonempty_cacheable_object, $nonempty_metadata];

    return $data;
  }

}
