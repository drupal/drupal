<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\RendererBubblingTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\State\State;
use Drupal\Core\Cache\Cache;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 */
class RendererBubblingTest extends RendererTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Disable the required cache contexts, so that this test can test just the
    // bubbling behavior.
    $this->rendererConfig['required_cache_contexts'] = [];

    parent::setUp();
  }

  /**
   * Tests bubbling of assets when NOT using #pre_render callbacks.
   */
  public function testBubblingWithoutPreRender() {
    $this->setUpRequest();
    $this->setupMemoryCache();

    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnArgument(0);

    // Create an element with a child and subchild. Each element loads a
    // different library using #attached.
    $element = [
      '#type' => 'container',
      '#cache' => [
        'keys' => ['simpletest', 'renderer', 'children_attached'],
      ],
      '#attached' => ['library' => ['test/parent']],
      '#title' => 'Parent',
    ];
    $element['child'] = [
      '#type' => 'container',
      '#attached' => ['library' => ['test/child']],
      '#title' => 'Child',
    ];
    $element['child']['subchild'] = [
      '#attached' => ['library' => ['test/subchild']],
      '#markup' => 'Subchild',
    ];

    // Render the element and verify the presence of #attached JavaScript.
    $this->renderer->renderRoot($element);
    $expected_libraries = ['test/parent', 'test/child', 'test/subchild'];
    $this->assertEquals($element['#attached']['library'], $expected_libraries, 'The element, child and subchild #attached libraries are included.');

    // Load the element from cache and verify the presence of the #attached
    // JavaScript.
    $element = ['#cache' => ['keys' => ['simpletest', 'renderer', 'children_attached']]];
    $this->assertTrue(strlen($this->renderer->renderRoot($element)) > 0, 'The element was retrieved from cache.');
    $this->assertEquals($element['#attached']['library'], $expected_libraries, 'The element, child and subchild #attached libraries are included.');
  }

  /**
   * Tests cache context bubbling with a custom cache bin.
   */
  public function testContextBubblingCustomCacheBin() {
    $bin = $this->randomMachineName();

    $this->setUpRequest();
    $this->memoryCache = new MemoryBackend();
    $custom_cache = new MemoryBackend();

    $this->cacheFactory->expects($this->atLeastOnce())
      ->method('get')
      ->with($bin)
      ->willReturnCallback(function($requested_bin) use ($bin, $custom_cache) {
        if ($requested_bin === $bin) {
          return $custom_cache;
        }
        else {
          throw new \Exception();
        }
      });
    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnArgument(0);

    $build = [
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => ['foo'],
        'bin' => $bin,
      ],
      '#markup' => 'parent',
      'child' => [
        '#cache' => [
          'contexts' => ['bar'],
          'max-age' => 3600,
        ],
      ],
    ];
    $this->renderer->renderRoot($build);

    $this->assertRenderCacheItem('parent:foo', [
      '#cache_redirect' => TRUE,
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => ['bar', 'foo'],
        'tags' => [],
        'bin' => $bin,
        'max-age' => 3600,
      ],
    ], $bin);
  }

  /**
   * Tests cache context bubbling in edge cases, because it affects the CID.
   *
   * ::testBubblingWithPrerender() already tests the common case.
   *
   * @dataProvider providerTestContextBubblingEdgeCases
   */
  public function testContextBubblingEdgeCases(array $element, array $expected_top_level_contexts, array $expected_cache_items) {
    $this->setUpRequest();
    $this->setupMemoryCache();
    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnArgument(0);

    $this->renderer->renderRoot($element);

    $this->assertEquals($expected_top_level_contexts, $element['#cache']['contexts'], 'Expected cache contexts found.');
    foreach ($expected_cache_items as $cid => $expected_cache_item) {
      $this->assertRenderCacheItem($cid, $expected_cache_item);
    }
  }

  public function providerTestContextBubblingEdgeCases() {
    $data = [];

    // Cache contexts of inaccessible children aren't bubbled (because those
    // children are not rendered at all).
    $test_element = [
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => [],
      ],
      '#markup' => 'parent',
      'child' => [
        '#access' => FALSE,
        '#cache' => [
          'contexts' => ['foo'],
        ],
      ],
    ];
    $expected_cache_items = [
      'parent' => [
        '#attached' => [],
        '#cache' => [
          'contexts' => [],
          'tags' => [],
          'max-age' => Cache::PERMANENT,
        ],
        '#markup' => 'parent',
      ],
    ];
    $data[] = [$test_element, [], $expected_cache_items];

    // Assert cache contexts are sorted when they are used to generate a CID.
    // (Necessary to ensure that different render arrays where the same keys +
    // set of contexts are present point to the same cache item. Regardless of
    // the contexts' order. A sad necessity because PHP doesn't have sets.)
    $test_element = [
     '#cache' => [
        'keys' => ['set_test'],
        'contexts' => [],
      ],
    ];
    $expected_cache_items = [
      'set_test:bar:baz:foo' => [
        '#attached' => [],
        '#cache' => [
          'contexts' => [],
          'tags' => [],
          'max-age' => Cache::PERMANENT,
        ],
        '#markup' => '',
      ],
    ];
    $context_orders = [
      ['foo', 'bar', 'baz'],
      ['foo', 'baz', 'bar'],
      ['bar', 'foo', 'baz'],
      ['bar', 'baz', 'foo'],
      ['baz', 'foo', 'bar'],
      ['baz', 'bar', 'foo'],
    ];
    foreach ($context_orders as $context_order) {
      $test_element['#cache']['contexts'] = $context_order;
      sort($context_order);
      $expected_cache_items['set_test:bar:baz:foo']['#cache']['contexts'] = $context_order;
      $data[] = [$test_element, $context_order, $expected_cache_items];
    }

    // A parent with a certain set of cache contexts is unaffected by a child
    // that has a subset of those contexts.
    $test_element = [
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => ['foo', 'bar', 'baz'],
      ],
      '#markup' => 'parent',
      'child' => [
        '#cache' => [
          'contexts' => ['foo', 'baz'],
          'max-age' => 3600,
        ],
      ],
    ];
    $expected_cache_items = [
      'parent:bar:baz:foo' => [
        '#attached' => [],
        '#cache' => [
          'contexts' => ['bar', 'baz', 'foo'],
          'tags' => [],
          'max-age' => 3600,
        ],
        '#markup' => 'parent',
      ],
    ];
    $data[] = [$test_element, ['bar', 'baz', 'foo'], $expected_cache_items];

    // A parent with a certain set of cache contexts that is a subset of the
    // cache contexts of a child gets a redirecting cache item for the cache ID
    // created pre-bubbling (without the child's additional cache contexts). It
    // points to a cache item with a post-bubbling cache ID (i.e. with the
    // child's additional cache contexts).
    // Furthermore, the redirecting cache item also includes the children's
    // cache tags, since changes in the children may cause those children to get
    // different cache contexts and therefore cause different cache contexts to
    // be stored in the redirecting cache item.
    $test_element = [
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => ['foo'],
        'tags' => ['yar', 'har'],
      ],
      '#markup' => 'parent',
      'child' => [
        '#cache' => [
          'contexts' => ['bar'],
          'tags' => ['fiddle', 'dee'],
        ],
        '#markup' => '',
      ],
    ];
    $expected_cache_items = [
      'parent:foo' => [
        '#cache_redirect' => TRUE,
        '#cache' => [
          // The keys + contexts this redirects to.
          'keys' => ['parent'],
          'contexts' => ['bar', 'foo'],
          'tags' => ['dee', 'fiddle', 'har', 'yar'],
          'bin' => 'render',
          'max-age' => Cache::PERMANENT,
        ],
      ],
      'parent:bar:foo' => [
        '#attached' => [],
        '#cache' => [
          'contexts' => ['bar', 'foo'],
          'tags' => ['dee', 'fiddle', 'har', 'yar'],
          'max-age' => Cache::PERMANENT,
        ],
        '#markup' => 'parent',
      ],
    ];
    $data[] = [$test_element, ['bar', 'foo'], $expected_cache_items];

    return $data;
  }

  /**
   * Tests the self-healing of the redirect with conditional cache contexts.
   */
  public function testConditionalCacheContextBubblingSelfHealing() {
    $current_user_role = &$this->currentUserRole;

    $this->setUpRequest();
    $this->setupMemoryCache();

    $test_element = [
      '#cache' => [
        'keys' => ['parent'],
        'tags' => ['a'],
      ],
      '#markup' => 'parent',
      'child' => [
        '#cache' => [
          'contexts' => ['user.roles'],
          'tags' => ['b'],
        ],
        'grandchild' => [
          '#access_callback' => function() use (&$current_user_role) {
            // Only role A cannot access this subtree.
            return $current_user_role !== 'A';
          },
          '#cache' => [
            'contexts' => ['foo'],
            'tags' => ['c'],
            // A lower max-age; the redirecting cache item should be updated.
            'max-age' => 1800,
          ],
          'grandgrandchild' => [
            '#access_callback' => function () use (&$current_user_role) {
              // Only role C can access this subtree.
              return $current_user_role === 'C';
            },
            '#cache' => [
              'contexts' => ['bar'],
              'tags' => ['d'],
              // A lower max-age; the redirecting cache item should be updated.
              'max-age' => 300,
            ],
          ],
        ],
      ],
    ];

    // Request 1: role A, the grandchild isn't accessible => bubbled cache
    // contexts: user.roles.
    $element = $test_element;
    $current_user_role = 'A';
    $this->renderer->renderRoot($element);
    $this->assertRenderCacheItem('parent', [
      '#cache_redirect' => TRUE,
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => ['user.roles'],
        'tags' => ['a', 'b'],
        'bin' => 'render',
        'max-age' => Cache::PERMANENT,
      ],
    ]);
    $this->assertRenderCacheItem('parent:r.A', [
      '#attached' => [],
      '#cache' => [
        'contexts' => ['user.roles'],
        'tags' => ['a', 'b'],
        'max-age' => Cache::PERMANENT,
      ],
      '#markup' => 'parent',
    ]);

    // Request 2: role B, the grandchild is accessible => bubbled cache
    // contexts: foo, user.roles + merged max-age: 1800.
    $element = $test_element;
    $current_user_role = 'B';
    $this->renderer->renderRoot($element);
    $this->assertRenderCacheItem('parent', [
      '#cache_redirect' => TRUE,
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => ['foo', 'user.roles'],
        'tags' => ['a', 'b', 'c'],
        'bin' => 'render',
        'max-age' => 1800,
      ],
    ]);
    $this->assertRenderCacheItem('parent:foo:r.B', [
      '#attached' => [],
      '#cache' => [
        'contexts' => ['foo', 'user.roles'],
        'tags' => ['a', 'b', 'c'],
        'max-age' => 1800,
      ],
      '#markup' => 'parent',
    ]);

    // Request 3: role A again, the grandchild is inaccessible again => bubbled
    // cache contexts: user.roles; but that's a subset of the already-bubbled
    // cache contexts, so nothing is actually changed in the redirecting cache
    // item. However, the cache item we were looking for in request 1 is
    // technically the same one we're looking for now (it's the exact same
    // request), but with one additional cache context. This is necessary to
    // avoid "cache ping-pong". (Requests 1 and 3 are identical, but without the
    // right merging logic to handle request 2, the redirecting cache item would
    // toggle between only the 'user.roles' cache context and both the 'foo'
    // and 'user.roles' cache contexts, resulting in a cache miss every time.)
    $element = $test_element;
    $current_user_role = 'A';
    $this->renderer->renderRoot($element);
    $this->assertRenderCacheItem('parent', [
      '#cache_redirect' => TRUE,
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => ['foo', 'user.roles'],
        'tags' => ['a', 'b', 'c'],
        'bin' => 'render',
        'max-age' => 1800,
      ],
    ]);
    $this->assertRenderCacheItem('parent:foo:r.A', [
      '#attached' => [],
      '#cache' => [
        'contexts' => ['foo', 'user.roles'],
        'tags' => ['a', 'b'],
        // Note that the max-age here is unaffected. When role A, the grandchild
        // is never rendered, so neither is its max-age of 1800 present here,
        // despite 1800 being the max-age of the redirecting cache item.
        'max-age' => Cache::PERMANENT,
      ],
      '#markup' => 'parent',
    ]);

    // Request 4: role C, both the grandchild and the grandgrandchild are
    // accessible => bubbled cache contexts: foo, bar, user.roles + merged
    // max-age: 300.
    $element = $test_element;
    $current_user_role = 'C';
    $this->renderer->renderRoot($element);
    $final_parent_cache_item = [
      '#cache_redirect' => TRUE,
      '#cache' => [
        'keys' => ['parent'],
        'contexts' => ['bar', 'foo', 'user.roles'],
        'tags' => ['a', 'b', 'c', 'd'],
        'bin' => 'render',
        'max-age' => 300,
      ],
    ];
    $this->assertRenderCacheItem('parent', $final_parent_cache_item);
    $this->assertRenderCacheItem('parent:bar:foo:r.C', [
      '#attached' => [],
      '#cache' => [
        'contexts' => ['bar', 'foo', 'user.roles'],
        'tags' => ['a', 'b', 'c', 'd'],
        'max-age' => 300,
      ],
      '#markup' => 'parent',
    ]);

    // Request 5: role A again, verifying the merging like we did for request 3.
    $element = $test_element;
    $current_user_role = 'A';
    $this->renderer->renderRoot($element);
    $this->assertRenderCacheItem('parent', $final_parent_cache_item);
    $this->assertRenderCacheItem('parent:bar:foo:r.A', [
      '#attached' => [],
      '#cache' => [
        'contexts' => ['bar', 'foo', 'user.roles'],
        'tags' => ['a', 'b'],
        // Note that the max-age here is unaffected. When role A, the grandchild
        // is never rendered, so neither is its max-age of 1800 present here,
        // nor the grandgrandchild's max-age of 300, despite 300 being the
        // max-age of the redirecting cache item.
        'max-age' => Cache::PERMANENT,
      ],
      '#markup' => 'parent',
    ]);

    // Request 6: role B again, verifying the merging like we did for request 3.
    $element = $test_element;
    $current_user_role = 'B';
    $this->renderer->renderRoot($element);
    $this->assertRenderCacheItem('parent', $final_parent_cache_item);
    $this->assertRenderCacheItem('parent:bar:foo:r.B', [
      '#attached' => [],
      '#cache' => [
        'contexts' => ['bar', 'foo', 'user.roles'],
        'tags' => ['a', 'b', 'c'],
        // Note that the max-age here is unaffected. When role B, the
        // grandgrandchild is never rendered, so neither is its max-age of 300
        // present here, despite 300 being the max-age of the redirecting cache
        // item.
        'max-age' => 1800,
      ],
      '#markup' => 'parent',
    ]);
  }

  /**
   * Tests bubbling of bubbleable metadata added by #pre_render callbacks.
   *
   * @dataProvider providerTestBubblingWithPrerender
   */
  public function testBubblingWithPrerender($test_element) {
    $this->setUpRequest();
    $this->setupMemoryCache();

    // Mock the State service.
    $memory_state = new State(new KeyValueMemoryFactory(), new MemoryBackend('test'), new NullLockBackend());
    \Drupal::getContainer()->set('state', $memory_state);
    $this->controllerResolver->expects($this->any())
      ->method('getControllerFromDefinition')
      ->willReturnArgument(0);

    // Simulate the theme system/Twig: a recursive call to Renderer::render(),
    // just like the theme system or a Twig template would have done.
    $this->themeManager->expects($this->any())
      ->method('render')
      ->willReturnCallback(function ($hook, $vars) {
        return $this->renderer->render($vars['foo']);
      });

    // ::bubblingPreRender() verifies that a #pre_render callback for a render
    // array that is cacheable and …
    // - … is cached does NOT get called. (Also mock a render cache item.)
    // - … is not cached DOES get called.
    \Drupal::state()->set('bubbling_nested_pre_render_cached', FALSE);
    \Drupal::state()->set('bubbling_nested_pre_render_uncached', FALSE);
    $this->memoryCache->set('cached_nested', ['#markup' => 'Cached nested!', '#attached' => [], '#cache' => ['contexts' => [], 'tags' => []]]);

    // Simulate the rendering of an entire response (i.e. a root call).
    $output = $this->renderer->renderRoot($test_element);

    // First, assert the render array is of the expected form.
    $this->assertEquals('Cache context!Cache tag!Asset!Placeholder!barquxNested!Cached nested!', trim($output), 'Expected HTML generated.');
    $this->assertEquals(['child.cache_context'], $test_element['#cache']['contexts'], 'Expected cache contexts found.');
    $this->assertEquals(['child:cache_tag'], $test_element['#cache']['tags'], 'Expected cache tags found.');
    $expected_attached = [
      'drupalSettings' => ['foo' => 'bar'],
      'placeholders' => [],
    ];
    $this->assertEquals($expected_attached, $test_element['#attached'], 'Expected attachments found.');

    // Second, assert that #pre_render callbacks are only executed if they don't
    // have a render cache hit (and hence a #pre_render callback for a render
    // cached item cannot bubble more metadata).
    $this->assertTrue(\Drupal::state()->get('bubbling_nested_pre_render_uncached'));
    $this->assertFalse(\Drupal::state()->get('bubbling_nested_pre_render_cached'));
  }

  /**
   * Provides two test elements: one without, and one with the theme system.
   *
   * @return array
   */
  public function providerTestBubblingWithPrerender() {
    $data = [];

    // Test element without theme.
    $data[] = [
      [
        'foo' => [
          '#pre_render' => [__NAMESPACE__ . '\\BubblingTest::bubblingPreRender'],
        ],
      ],
    ];

    // Test element with theme.
    $data[] = [
      [
        '#theme' => 'common_test_render_element',
        'foo' => [
          '#pre_render' => [__NAMESPACE__ . '\\BubblingTest::bubblingPreRender'],
        ],
      ],
    ];

    return $data;
  }

  /**
   * Tests that an element's cache keys cannot be changed during its rendering.
   */
  public function testOverWriteCacheKeys() {
    $this->setUpRequest();
    $this->setupMemoryCache();

    // Ensure a logic exception
    $data = [
      '#cache' => [
        'keys' => ['llama', 'bar'],
       ],
      '#pre_render' => [__NAMESPACE__ . '\\BubblingTest::bubblingCacheOverwritePrerender'],
    ];
    $this->setExpectedException(\LogicException::class, 'Cache keys may not be changed after initial setup. Use the contexts property instead to bubble additional metadata.');
    $this->renderer->renderRoot($data);
  }

}


class BubblingTest {

  /**
   * #pre_render callback for testBubblingWithPrerender().
   */
  public static function bubblingPreRender($elements) {
    $elements += [
      'child_cache_context' => [
        '#cache' => [
          'contexts' => ['child.cache_context'],
        ],
        '#markup' => 'Cache context!',
      ],
      'child_cache_tag' => [
        '#cache' => [
          'tags' => ['child:cache_tag'],
        ],
        '#markup' => 'Cache tag!',
      ],
      'child_asset' => [
        '#attached' => [
          'drupalSettings' => ['foo' => 'bar'],
        ],
        '#markup' => 'Asset!',
      ],
      'child_placeholder' => [
        '#create_placeholder' => TRUE,
        '#lazy_builder' => [__CLASS__ . '::bubblingPlaceholder', ['bar', 'qux']],
      ],
      'child_nested_pre_render_uncached' => [
        '#cache' => ['keys' => ['uncached_nested']],
        '#pre_render' => [__CLASS__ . '::bubblingNestedPreRenderUncached'],
      ],
      'child_nested_pre_render_cached' => [
        '#cache' => ['keys' => ['cached_nested']],
        '#pre_render' => [__CLASS__ . '::bubblingNestedPreRenderCached'],
      ],
    ];
    return $elements;
  }

  /**
   * #pre_render callback for testBubblingWithPrerender().
   */
  public static function bubblingNestedPreRenderUncached($elements) {
    \Drupal::state()->set('bubbling_nested_pre_render_uncached', TRUE);
    $elements['#markup'] = 'Nested!';
    return $elements;
  }

  /**
   * #pre_render callback for testBubblingWithPrerender().
   */
  public static function bubblingNestedPreRenderCached($elements) {
    \Drupal::state()->set('bubbling_nested_pre_render_cached', TRUE);
    return $elements;
  }

  /**
   * #lazy_builder callback for testBubblingWithPrerender().
   */
  public static function bubblingPlaceholder($foo, $baz) {
    return [
      '#markup' => 'Placeholder!' . $foo . $baz,
    ];
  }

  /**
   * #pre_render callback for testOverWriteCacheKeys().
   */
  public static function bubblingCacheOverwritePrerender($elements) {
    // Overwrite the #cache entry with new data.
    $elements['#cache'] = [
      'keys' => ['llama', 'foo'],
    ];
    $elements['#markup'] = 'Setting cache keys just now!';
    return $elements;
  }

}
