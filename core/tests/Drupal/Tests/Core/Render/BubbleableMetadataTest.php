<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\BubbleableMetadataTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Render\BubbleableMetadata
 * @group Render
 */
class BubbleableMetadataTest extends UnitTestCase {

  /**
   * @covers ::merge
   * @dataProvider providerTestMerge
   *
   * This only tests at a high level, because it reuses existing logic. Detailed
   * tests exist for the existing logic:
   *
   * @see \Drupal\Tests\Core\Cache\CacheTest::testMergeTags()
   * @see \Drupal\Tests\Core\Cache\CacheTest::testMergeMaxAges()
   * @see \Drupal\Tests\Core\Cache\CacheContextsTest
   * @see \Drupal\system\Tests\Common\MergeAttachmentsTest
   * @see \Drupal\Tests\Core\Render\RendererPostRenderCacheTest
   */
  public function testMerge(BubbleableMetadata $a, CacheableMetadata $b, BubbleableMetadata $expected) {
    // Verify that if the second operand is a CacheableMetadata object, not a
    // BubbleableMetadata object, that BubbleableMetadata::merge() doesn't
    // attempt to merge assets.
    if (!$b instanceof BubbleableMetadata) {
      $renderer = $this->getMockBuilder('Drupal\Core\Render\Renderer')
        ->disableOriginalConstructor()
        ->getMock();
      $renderer->expects($this->never())
        ->method('mergeAttachments');
    }
    // Otherwise, let the original ::mergeAttachments() method be executed.
    else {
      $renderer = $this->getMockBuilder('Drupal\Core\Render\Renderer')
        ->disableOriginalConstructor()
        ->setMethods(NULL)
        ->getMock();
    }

    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    $container->set('renderer', $renderer);
    \Drupal::setContainer($container);

    $this->assertEquals($expected, $a->merge($b));
  }

  /**
   * Provides test data for testMerge().
   *
   * @return array
   */
  public function providerTestMerge() {
    return [
      // Second operand is a BubbleableMetadata object.
      // All empty.
      [(new BubbleableMetadata()), (new BubbleableMetadata()), (new BubbleableMetadata())],
      // Cache contexts.
      [(new BubbleableMetadata())->setCacheContexts(['foo']), (new BubbleableMetadata())->setCacheContexts(['bar']), (new BubbleableMetadata())->setCacheContexts(['bar', 'foo'])],
      // Cache tags.
      [(new BubbleableMetadata())->setCacheTags(['foo']), (new BubbleableMetadata())->setCacheTags(['bar']), (new BubbleableMetadata())->setCacheTags(['bar', 'foo'])],
      // Cache max-ages.
      [(new BubbleableMetadata())->setCacheMaxAge(60), (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT), (new BubbleableMetadata())->setCacheMaxAge(60)],
      // Assets.
      [(new BubbleableMetadata())->setAssets(['library' => ['core/foo']]), (new BubbleableMetadata())->setAssets(['library' => ['core/bar']]), (new BubbleableMetadata())->setAssets(['library' => ['core/foo', 'core/bar']])],
      // #post_render_cache callbacks.
      [(new BubbleableMetadata())->setPostRenderCacheCallbacks(['callback' => [['token' => 'A']]]), (new BubbleableMetadata())->setPostRenderCacheCallbacks(['callback' => [['token' => 'B']]]), (new BubbleableMetadata())->setPostRenderCacheCallbacks(['callback' => [['token' => 'A'], ['token' => 'B']]])],

      // Second operand is a CacheableMetadata object.
      // All empty.
      [(new BubbleableMetadata()), (new CacheableMetadata()), (new BubbleableMetadata())],
      // Cache contexts.
      [(new BubbleableMetadata())->setCacheContexts(['foo']), (new CacheableMetadata())->setCacheContexts(['bar']), (new BubbleableMetadata())->setCacheContexts(['bar', 'foo'])],
      // Cache tags.
      [(new BubbleableMetadata())->setCacheTags(['foo']), (new CacheableMetadata())->setCacheTags(['bar']), (new BubbleableMetadata())->setCacheTags(['bar', 'foo'])],
      // Cache max-ages.
      [(new BubbleableMetadata())->setCacheMaxAge(60), (new CacheableMetadata())->setCacheMaxAge(Cache::PERMANENT), (new BubbleableMetadata())->setCacheMaxAge(60)],
    ];
  }

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

}
