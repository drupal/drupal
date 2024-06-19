<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\PlaceholderingRenderCache;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @covers \Drupal\Core\Render\RenderCache
 * @covers \Drupal\Core\Render\PlaceholderingRenderCache
 * @group Render
 */
class RendererPlaceholdersTest extends RendererTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Disable the required cache contexts, so that this test can test just the
    // placeholder replacement behavior.
    $this->rendererConfig['required_cache_contexts'] = [];

    parent::setUp();
  }

  /**
   * Provides the two classes of placeholders: cacheable and uncacheable.
   *
   * I.e. with or without #cache[keys].
   *
   * Also, different types:
   * - A) automatically generated placeholder
   *   - 1) manually triggered (#create_placeholder = TRUE)
   *   - 2) automatically triggered (based on max-age = 0 at the top level)
   *   - 3) automatically triggered (based on high cardinality cache contexts at
   *        the top level)
   *   - 4) automatically triggered (based on high-invalidation frequency cache
   *        tags at the top level)
   *   - 5) automatically triggered (based on max-age = 0 in its subtree, i.e.
   *        via bubbling)
   *   - 6) automatically triggered (based on high cardinality cache contexts in
   *        its subtree, i.e. via bubbling)
   *   - 7) automatically triggered (based on high-invalidation frequency cache
   *        tags in its subtree, i.e. via bubbling)
   * - B) manually generated placeholder
   *
   * So, in total 2*8 = 16 permutations. (On one axis: uncacheable vs.
   * uncacheable = 2; on the other axis: A1–7 and B = 8.)
   *
   * @todo Case A5 is not yet supported by core. So that makes for only 14
   *   permutations currently, instead of 16. That will be done in
   *   https://www.drupal.org/node/2559847
   *
   * @return array
   */
  public static function providerPlaceholders(): array {
    $args = [static::randomContextValue()];

    $generate_placeholder_markup = function ($cache_keys = NULL) use ($args) {
      $token_render_array = [
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
      ];
      if (is_array($cache_keys)) {
        $token_render_array['#cache']['keys'] = $cache_keys;
      }
      $token = Crypt::hashBase64(serialize($token_render_array));
      // \Drupal\Core\Render\Markup::create() is necessary as the render
      // system would mangle this markup. As this is exactly what happens at
      // runtime this is a valid use-case.
      return Markup::create('<drupal-render-placeholder callback="Drupal\Tests\Core\Render\PlaceholdersTest::callback" arguments="' . '0=' . $args[0] . '" token="' . $token . '"></drupal-render-placeholder>');
    };

    $extract_placeholder_render_array = function ($placeholder_render_array) {
      return array_intersect_key($placeholder_render_array, ['#lazy_builder' => TRUE, '#cache' => TRUE]);
    };

    // Note the presence of '#create_placeholder'.
    $base_element_a1 = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
      'placeholder' => [
        '#cache' => [
          'contexts' => [],
        ],
        '#create_placeholder' => TRUE,
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
      ],
    ];
    // Note the absence of '#create_placeholder', presence of max-age=0 at the
    // top level.
    $base_element_a2 = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
      'placeholder' => [
        '#cache' => [
          'contexts' => [],
          'max-age' => 0,
        ],
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
      ],
    ];
    // Note the absence of '#create_placeholder', presence of high cardinality
    // cache context at the top level.
    $base_element_a3 = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
      'placeholder' => [
        '#cache' => [
          'contexts' => ['user'],
        ],
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
      ],
    ];
    // Note the absence of '#create_placeholder', presence of high-invalidation
    // frequency cache tag at the top level.
    $base_element_a4 = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
      'placeholder' => [
        '#cache' => [
          'contexts' => [],
          'tags' => ['current-temperature'],
        ],
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
      ],
    ];
    // Note the absence of '#create_placeholder', presence of max-age=0 created
    // by the #lazy_builder callback.
    // @todo in https://www.drupal.org/node/2559847
    $base_element_a5 = [];
    // Note the absence of '#create_placeholder', presence of high cardinality
    // cache context created by the #lazy_builder callback.
    // @see \Drupal\Tests\Core\Render\PlaceholdersTest::callbackPerUser()
    $base_element_a6 = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
      'placeholder' => [
        '#cache' => [
          'contexts' => [],
        ],
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callbackPerUser', $args],
      ],
    ];
    // Note the absence of '#create_placeholder', presence of high-invalidation
    // frequency cache tag created by the #lazy_builder callback.
    // @see \Drupal\Tests\Core\Render\PlaceholdersTest::callbackTagCurrentTemperature()
    $base_element_a7 = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
      'placeholder' => [
        '#cache' => [
          'contexts' => [],
        ],
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callbackTagCurrentTemperature', $args],
      ],
    ];
    // Note the absence of '#create_placeholder', but the presence of
    // '#attached[placeholders]'.
    $base_element_b = [
      '#markup' => $generate_placeholder_markup(),
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
        'placeholders' => [
          (string) $generate_placeholder_markup() => [
            '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
          ],
        ],
      ],
    ];

    $keys = ['placeholder', 'output', 'can', 'be', 'render', 'cached', 'too'];

    $cases = [];

    // Case one: render array that has a placeholder that is:
    // - automatically created, but manually triggered (#create_placeholder = TRUE)
    // - uncacheable
    $element_without_cache_keys = $base_element_a1;
    $expected_placeholder_render_array = $extract_placeholder_render_array($base_element_a1['placeholder']);
    $cases[] = [
      $element_without_cache_keys,
      $args,
      $expected_placeholder_render_array,
      FALSE,
      [],
      [],
      [],
    ];

    // Case two: render array that has a placeholder that is:
    // - automatically created, but manually triggered (#create_placeholder = TRUE)
    // - cacheable
    $element_with_cache_keys = $base_element_a1;
    $element_with_cache_keys['placeholder']['#cache']['keys'] = $keys;
    $expected_placeholder_render_array['#cache']['keys'] = $keys;
    $cases[] = [
      $element_with_cache_keys,
      $args,
      $expected_placeholder_render_array,
      $keys,
      [],
      [],
      [
        '#markup' => '<p>This is a rendered placeholder!</p>',
        '#attached' => [
          'drupalSettings' => [
            'dynamic_animal' => $args[0],
          ],
        ],
        '#cache' => [
          'contexts' => [],
          'tags' => [],
          'max-age' => Cache::PERMANENT,
        ],
      ],
    ];

    // Case three: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to max-age=0
    // - uncacheable
    $element_without_cache_keys = $base_element_a2;
    $expected_placeholder_render_array = $extract_placeholder_render_array($base_element_a2['placeholder']);
    $cases[] = [
      $element_without_cache_keys,
      $args,
      $expected_placeholder_render_array,
      FALSE,
      [],
      [],
      [],
    ];

    // Case four: render array that has a placeholder that is:
    // - automatically created, but automatically triggered due to max-age=0
    // - cacheable
    $element_with_cache_keys = $base_element_a2;
    $element_with_cache_keys['placeholder']['#cache']['keys'] = $keys;
    $expected_placeholder_render_array['#cache']['keys'] = $keys;
    $cases[] = [
      $element_with_cache_keys,
      $args,
      $expected_placeholder_render_array,
      FALSE,
      [],
      [],
      [],
    ];

    // Case five: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to high
    //   cardinality cache contexts
    // - uncacheable
    $element_without_cache_keys = $base_element_a3;
    $expected_placeholder_render_array = $extract_placeholder_render_array($base_element_a3['placeholder']);
    $cases[] = [
      $element_without_cache_keys,
      $args,
      $expected_placeholder_render_array,
      FALSE,
      [],
      [],
      [],
    ];

    // Case six: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to high
    //   cardinality cache contexts
    // - cacheable
    $element_with_cache_keys = $base_element_a3;
    $element_with_cache_keys['placeholder']['#cache']['keys'] = $keys;
    $expected_placeholder_render_array['#cache']['keys'] = $keys;
    $cases[] = [
      $element_with_cache_keys,
      $args,
      $expected_placeholder_render_array,
      $keys,
      [],
      [],
      [
        '#markup' => '<p>This is a rendered placeholder!</p>',
        '#attached' => [
          'drupalSettings' => [
            'dynamic_animal' => $args[0],
          ],
        ],
        '#cache' => [
          'contexts' => ['user'],
          'tags' => [],
          'max-age' => Cache::PERMANENT,
        ],
      ],
    ];

    // Case seven: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to high
    //   invalidation frequency cache tags
    // - uncacheable
    $element_without_cache_keys = $base_element_a4;
    $expected_placeholder_render_array = $extract_placeholder_render_array($base_element_a4['placeholder']);
    $cases[] = [
      $element_without_cache_keys,
      $args,
      $expected_placeholder_render_array,
      FALSE,
      [],
      [],
      [],
    ];

    // Case eight: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to high
    //   invalidation frequency cache tags
    // - cacheable
    $element_with_cache_keys = $base_element_a4;
    $element_with_cache_keys['placeholder']['#cache']['keys'] = $keys;
    $expected_placeholder_render_array['#cache']['keys'] = $keys;
    $cases[] = [
      $element_with_cache_keys,
      $args,
      $expected_placeholder_render_array,
      $keys,
      [],
      [],
      [
        '#markup' => '<p>This is a rendered placeholder!</p>',
        '#attached' => [
          'drupalSettings' => [
            'dynamic_animal' => $args[0],
          ],
        ],
        '#cache' => [
          'contexts' => [],
          'tags' => ['current-temperature'],
          'max-age' => Cache::PERMANENT,
        ],
      ],
    ];

    // Case nine: render array that DOES NOT have a placeholder that is:
    // - NOT created, despite max-age=0 that is bubbled
    // - uncacheable
    // (because the render element with #lazy_builder does not have #cache[keys]
    // and hence the max-age=0 bubbles up further)
    // @todo in https://www.drupal.org/node/2559847

    // Case ten: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to max-age=0
    //   that is bubbled
    // - cacheable
    // @todo in https://www.drupal.org/node/2559847

    // Case eleven: render array that DOES NOT have a placeholder that is:
    // - NOT created, despite high cardinality cache contexts that are bubbled
    // - uncacheable
    $element_without_cache_keys = $base_element_a6;
    $expected_placeholder_render_array = $extract_placeholder_render_array($base_element_a6['placeholder']);
    $cases[] = [
      $element_without_cache_keys,
      $args,
      $expected_placeholder_render_array,
      FALSE,
      ['user'],
      [],
      [],
    ];

    // Case twelve: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to high
    //   cardinality cache contexts that are bubbled
    // - cacheable
    $element_with_cache_keys = $base_element_a6;
    $element_with_cache_keys['placeholder']['#cache']['keys'] = $keys;
    $expected_placeholder_render_array['#cache']['keys'] = $keys;
    $cases[] = [
      $element_with_cache_keys,
      $args,
      $expected_placeholder_render_array,
      $keys,
      ['user'],
      [],
      [
        '#markup' => '<p>This is a rendered placeholder!</p>',
        '#attached' => [
          'drupalSettings' => [
            'dynamic_animal' => $args[0],
          ],
        ],
        '#cache' => [
          'contexts' => ['user'],
          'tags' => [],
          'max-age' => Cache::PERMANENT,
        ],
      ],
    ];

    // Case thirteen: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to high
    //   invalidation frequency cache tags that are bubbled
    // - uncacheable
    $element_without_cache_keys = $base_element_a7;
    $expected_placeholder_render_array = $extract_placeholder_render_array($base_element_a7['placeholder']);
    $cases[] = [
      $element_without_cache_keys,
      $args,
      $expected_placeholder_render_array,
      FALSE,
      [],
      ['current-temperature'],
      [],
    ];

    // Case fourteen: render array that has a placeholder that is:
    // - automatically created, and automatically triggered due to high
    //   invalidation frequency cache tags that are bubbled
    // - cacheable
    $element_with_cache_keys = $base_element_a7;
    $element_with_cache_keys['placeholder']['#cache']['keys'] = $keys;
    $expected_placeholder_render_array['#cache']['keys'] = $keys;
    $cases[] = [
      $element_with_cache_keys,
      $args,
      $expected_placeholder_render_array,
      $keys,
      [],
      [],
      [
        '#markup' => '<p>This is a rendered placeholder!</p>',
        '#attached' => [
          'drupalSettings' => [
            'dynamic_animal' => $args[0],
          ],
        ],
        '#cache' => [
          'contexts' => [],
          'tags' => ['current-temperature'],
          'max-age' => Cache::PERMANENT,
        ],
      ],
    ];

    // Case fifteen: render array that has a placeholder that is:
    // - manually created
    // - uncacheable
    $x = $base_element_b;
    $expected_placeholder_render_array = $x['#attached']['placeholders'][(string) $generate_placeholder_markup()];
    self::assertArrayNotHasKey('#cache', $expected_placeholder_render_array);
    $cases[] = [
      $x,
      $args,
      $expected_placeholder_render_array,
      FALSE,
      [],
      [],
      [],
    ];

    // Case sixteen: render array that has a placeholder that is:
    // - manually created
    // - cacheable
    $x = $base_element_b;
    $x['#markup'] = $placeholder_markup = $generate_placeholder_markup($keys);
    $placeholder_markup = (string) $placeholder_markup;
    $x['#attached']['placeholders'] = [
      $placeholder_markup => [
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
        '#cache' => ['keys' => $keys],
      ],
    ];
    $expected_placeholder_render_array = $x['#attached']['placeholders'][$placeholder_markup];
    $cases[] = [
      $x,
      $args,
      $expected_placeholder_render_array,
      $keys,
      [],
      [],
      [
        '#markup' => '<p>This is a rendered placeholder!</p>',
        '#attached' => [
          'drupalSettings' => [
            'dynamic_animal' => $args[0],
          ],
        ],
        '#cache' => [
          'contexts' => [],
          'tags' => [],
          'max-age' => Cache::PERMANENT,
        ],
      ],
    ];

    return $cases;
  }

  /**
   * Generates an element with a placeholder.
   *
   * @return array
   *   An array containing:
   *   - A render array containing a placeholder.
   *   - The context used for that #lazy_builder callback.
   */
  protected function generatePlaceholderElement() {
    $args = [static::randomContextValue()];
    $test_element = [];
    $test_element['#attached']['drupalSettings']['foo'] = 'bar';
    $test_element['placeholder']['#cache']['keys'] = ['placeholder', 'output', 'can', 'be', 'render', 'cached', 'too'];
    $test_element['placeholder']['#cache']['contexts'] = [];
    $test_element['placeholder']['#create_placeholder'] = TRUE;
    $test_element['placeholder']['#lazy_builder'] = ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args];

    return [$test_element, $args];
  }

  /**
   * @param false|array $cache_keys
   *   The cache keys.
   * @param array $expected_data
   *   A render array with the expected values.
   *
   * @internal
   */
  protected function assertPlaceholderRenderCache($cache_keys, array $expected_data) {
    if ($cache_keys !== FALSE) {
      $cached = $this->memoryCache->get($cache_keys, CacheableMetadata::createFromRenderArray($expected_data));
      $cached_element = $cached->data;
      $this->assertEquals($expected_data, $cached_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by the placeholder being replaced.');
    }
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @dataProvider providerPlaceholders
   */
  public function testUncacheableParent(array $element, array $args, array $expected_placeholder_render_array, array|false $placeholder_cache_keys, array $bubbled_cache_contexts, array $bubbled_cache_tags, array $placeholder_expected_render_cache_array): void {
    if ($placeholder_cache_keys) {
      $this->setUpMemoryCache();
    }
    else {
      $this->setUpUnusedCache();
    }

    $this->setUpRequest('GET');

    // No #cache on parent element.
    $element['#prefix'] = '<p>#cache disabled</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame('<p>#cache disabled</p><p>This is a rendered placeholder!</p>', (string) $output, 'Output is overridden.');
    $this->assertSame('<p>#cache disabled</p><p>This is a rendered placeholder!</p>', (string) $element['#markup'], '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'dynamic_animal' => $args[0],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the placeholder #lazy_builder callback exist.');
    $this->assertPlaceholderRenderCache($placeholder_cache_keys, $placeholder_expected_render_cache_array);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers \Drupal\Core\Render\RenderCache::set
   *
   * @dataProvider providerPlaceholders
   */
  public function testCacheableParent(array $test_element, array $args, array $expected_placeholder_render_array, array|false $placeholder_cache_keys, array $bubbled_cache_contexts, array $bubbled_cache_tags, array $placeholder_expected_render_cache_array): void {
    $element = $test_element;
    $this->setupMemoryCache();

    $this->setUpRequest('GET');

    $token = Crypt::hashBase64(serialize($expected_placeholder_render_array));
    $placeholder_callback = $expected_placeholder_render_array['#lazy_builder'][0];
    $expected_placeholder_markup = '<drupal-render-placeholder callback="' . $placeholder_callback . '" arguments="0=' . $args[0] . '" token="' . $token . '"></drupal-render-placeholder>';
    $this->assertSame($expected_placeholder_markup, Html::normalize($expected_placeholder_markup), 'Placeholder unaltered by Html::normalize() which is used by FilterHtmlCorrector.');

    // GET request: #cache enabled, cache miss.
    $element['#cache'] = ['keys' => ['placeholder_test_GET']];
    $element['#prefix'] = '<p>#cache enabled, GET</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame('<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>', (string) $output, 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame('<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>', (string) $element['#markup'], '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'dynamic_animal' => $args[0],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the placeholder #lazy_builder callback exist.');
    $this->assertPlaceholderRenderCache($placeholder_cache_keys, $placeholder_expected_render_cache_array);

    // GET request: validate cached data.
    $cached = $this->memoryCache->get(['placeholder_test_GET'], CacheableMetadata::createFromRenderArray($test_element));
    // There are three edge cases, where the shape of the render cache item for
    // the parent (with CID 'placeholder_test_GET') is vastly different. These
    // are the cases where:
    // - the placeholder is uncacheable (because it has no #cache[keys]), and;
    // - cacheability metadata that meets auto_placeholder_conditions is bubbled
    $has_uncacheable_lazy_builder = !isset($test_element['placeholder']['#cache']['keys']) && isset($test_element['placeholder']['#lazy_builder']);
    // Edge cases: always where both bubbling of an auto-placeholdering
    // condition happens from within a #lazy_builder that is uncacheable.
    // - uncacheable + A5 (cache max-age)
    // @todo in https://www.drupal.org/node/2559847
    // - uncacheable + A6 (cache context)
    $edge_case_a6_uncacheable = $has_uncacheable_lazy_builder && $test_element['placeholder']['#lazy_builder'][0] === 'Drupal\Tests\Core\Render\PlaceholdersTest::callbackPerUser';
    // - uncacheable + A7 (cache tag)
    $edge_case_a7_uncacheable = $has_uncacheable_lazy_builder && $test_element['placeholder']['#lazy_builder'][0] === 'Drupal\Tests\Core\Render\PlaceholdersTest::callbackTagCurrentTemperature';
    // The redirect-cacheable edge case: a high-cardinality cache context is
    // bubbled from a #lazy_builder callback for an uncacheable placeholder. The
    // element containing the uncacheable placeholder has cache keys set, and
    // due to the bubbled cache contexts it creates a cache redirect.
    if ($edge_case_a6_uncacheable) {
      $cached_element = $cached->data;
      $expected_element = [
        '#markup' => '<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>',
        '#attached' => [
          'drupalSettings' => [
            'foo' => 'bar',
            'dynamic_animal' => $args[0],
          ],
        ],
        '#cache' => [
          'contexts' => $bubbled_cache_contexts,
          'tags' => [],
          'max-age' => Cache::PERMANENT,
        ],
      ];
      $this->assertEquals($expected_element, $cached_element, 'The parent is render cached with a redirect in ase a cache context is bubbled from an uncacheable child (no #cache[keys]) with a #lazy_builder.');
    }
    // The normally cacheable edge case: a high-invalidation frequency cache tag
    // is bubbled from a #lazy_builder callback for an uncacheable placeholder.
    // The element containing the uncacheable placeholder has cache keys set,
    // and also has the bubbled cache tags.
    elseif ($edge_case_a7_uncacheable) {
      $cached_element = $cached->data;
      $expected_element = [
        '#markup' => '<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>',
        '#attached' => [
          'drupalSettings' => [
            'foo' => 'bar',
            'dynamic_animal' => $args[0],
          ],
        ],
        '#cache' => [
          'contexts' => [],
          'tags' => $bubbled_cache_tags,
          'max-age' => Cache::PERMANENT,
        ],
      ];
      $this->assertEquals($expected_element, $cached_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by placeholder #lazy_builder callbacks.');
    }
    // The regular case.
    else {
      $cached_element = $cached->data;
      $expected_element = [
        '#markup' => '<p>#cache enabled, GET</p>' . $expected_placeholder_markup,
        '#attached' => [
          'drupalSettings' => [
            'foo' => 'bar',
          ],
          'placeholders' => [
            $expected_placeholder_markup => [
              '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
            ],
          ],
        ],
        '#cache' => [
          'contexts' => [],
          'tags' => $bubbled_cache_tags,
          'max-age' => Cache::PERMANENT,
        ],
      ];
      $expected_element['#attached']['placeholders'][$expected_placeholder_markup] = $expected_placeholder_render_array;
      $this->assertEquals($expected_element, $cached_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by placeholder #lazy_builder callbacks.');
    }

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $element['#cache'] = ['keys' => ['placeholder_test_GET']];
    $element['#prefix'] = '<p>#cache enabled, GET</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame('<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>', (string) $output, 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame('<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>', (string) $element['#markup'], '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'dynamic_animal' => $args[0],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the placeholder #lazy_builder callback exist.');
  }

  /**
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers ::replacePlaceholders
   *
   * @dataProvider providerPlaceholders
   */
  public function testCacheableParentWithPostRequest(array $test_element, array $args): void {
    $this->setUpMemoryCache();

    // Verify behavior when handling a non-GET request, e.g. a POST request:
    // also in that case, placeholders must be replaced.
    $this->setUpRequest('POST');

    // POST request: #cache enabled, cache miss.
    $element = $test_element;
    $element['#cache'] = ['keys' => ['placeholder_test_POST']];
    $element['#prefix'] = '<p>#cache enabled, POST</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame('<p>#cache enabled, POST</p><p>This is a rendered placeholder!</p>', (string) $output, 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame('<p>#cache enabled, POST</p><p>This is a rendered placeholder!</p>', (string) $element['#markup'], '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'dynamic_animal' => $args[0],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the placeholder #lazy_builder callback exist.');

    // Even when the child element's placeholder is cacheable, it should not
    // generate a render cache item.
    $this->assertPlaceholderRenderCache(FALSE, []);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers \Drupal\Core\Render\PlaceholderingRenderCache::get
   * @covers \Drupal\Core\Render\PlaceholderingRenderCache::set
   * @covers ::replacePlaceholders
   *
   * @dataProvider providerPlaceholders
   */
  public function testPlaceholderingDisabledForPostRequests(array $test_element, array $args, array $expected_placeholder_render_array, array|false $placeholder_cache_keys): void {
    if ($placeholder_cache_keys && !empty($test_element['placeholder']['#cache']['keys'])) {
      $this->setUpMemoryCache();
    }
    else {
      $this->setUpUnusedCache();
    }
    $this->setUpRequest('POST');

    $element = $test_element;

    // Render without replacing placeholders, to allow this test to see which
    // #attached[placeholders] there are, if any.
    $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$element) {
      return $this->renderer->render($element);
    });
    // Only test cases where the placeholders have been specified manually are
    // allowed to have placeholders. This means that of the different situations
    // listed in providerPlaceholders(), only type B can have attached
    // placeholders. Everything else, whether:
    // 1. manual placeholdering
    // 2. automatic placeholdering via already-present cacheability metadata
    // 3. automatic placeholdering via bubbled cacheability metadata
    // All three of those should NOT result in placeholders.
    if (!isset($test_element['#attached']['placeholders'])) {
      $this->assertFalse(isset($element['#attached']['placeholders']), 'No placeholders created.');
    }
  }

  /**
   * Tests a placeholder that adds another placeholder.
   *
   * E.g. when rendering a node in a placeholder the rendering of that node
   * needs a placeholder of its own to be executed (to render the node links).
   *
   * @covers ::render
   * @covers ::doRender
   * @covers ::replacePlaceholders
   */
  public function testRecursivePlaceholder(): void {
    $args = [static::randomContextValue()];
    $element = [];
    $element['#create_placeholder'] = TRUE;
    $element['#lazy_builder'] = ['Drupal\Tests\Core\Render\RecursivePlaceholdersTest::callback', $args];

    $output = $this->renderer->renderRoot($element);
    $this->assertEquals('<p>This is a rendered placeholder!</p>', $output, 'The output has been modified by the indirect, recursive placeholder #lazy_builder callback.');
    $this->assertSame((string) $element['#markup'], '<p>This is a rendered placeholder!</p>', '#markup is overridden by the indirect, recursive placeholder #lazy_builder callback.');
    $expected_js_settings = [
      'dynamic_animal' => $args[0],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified by the indirect, recursive placeholder #lazy_builder callback.');
  }

  /**
   * @covers ::render
   * @covers ::doRender
   */
  public function testInvalidLazyBuilder(): void {
    $element = [];
    $element['#lazy_builder'] = '\Drupal\Tests\Core\Render\PlaceholdersTest::callback';

    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('The #lazy_builder property must have an array as a value.');
    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   */
  public function testInvalidLazyBuilderArguments(): void {
    $element = [];
    $element['#lazy_builder'] = ['\Drupal\Tests\Core\Render\PlaceholdersTest::callback', 'arg1', 'arg2'];

    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('The #lazy_builder property must have an array as a value, containing two values: the callback, and the arguments for the callback.');
    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @see testNonScalarLazyBuilderCallbackContext
   */
  public function testScalarLazyBuilderCallbackContext(): void {
    $element = [];
    $element['#lazy_builder'] = [
      '\Drupal\Tests\Core\Render\PlaceholdersTest::callback',
      [
        'string' => 'foo',
        'bool' => TRUE,
        'int' => 1337,
        'float' => 3.14,
        'null' => NULL,
      ],
    ];

    $result = $this->renderer->renderRoot($element);
    $this->assertInstanceOf('\Drupal\Core\Render\Markup', $result);
    $this->assertEquals('<p>This is a rendered placeholder!</p>', (string) $result);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   */
  public function testNonScalarLazyBuilderCallbackContext(): void {
    $element = [];
    $element['#lazy_builder'] = [
      '\Drupal\Tests\Core\Render\PlaceholdersTest::callback',
      [
        'string' => 'foo',
        'bool' => TRUE,
        'int' => 1337,
        'float' => 3.14,
        'null' => NULL,
        // array is not one of the scalar types.
        'array' => ['hi!'],
      ],
    ];

    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage("A #lazy_builder callback's context may only contain scalar values or NULL.");
    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   */
  public function testChildrenPlusBuilder(): void {
    $element = [];
    $element['#lazy_builder'] = ['Drupal\Tests\Core\Render\RecursivePlaceholdersTest::callback', []];
    $element['child_a']['#markup'] = 'Oh hai!';
    $element['child_b']['#markup'] = 'goodbye';

    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('When a #lazy_builder callback is specified, no children can exist; all children must be generated by the #lazy_builder callback. You specified the following children: child_a, child_b.');
    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   */
  public function testPropertiesPlusBuilder(): void {
    $element = [];
    $element['#lazy_builder'] = ['Drupal\Tests\Core\Render\RecursivePlaceholdersTest::callback', []];
    $element['#llama'] = '#awesome';
    $element['#piglet'] = '#cute';

    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('When a #lazy_builder callback is specified, no properties can exist; all properties must be generated by the #lazy_builder callback. You specified the following properties: #llama, #piglet.');
    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   */
  public function testCreatePlaceholderPropertyWithoutLazyBuilder(): void {
    $element = [];
    $element['#create_placeholder'] = TRUE;

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('When #create_placeholder is set, a #lazy_builder callback must be present as well.');
    $this->renderer->renderRoot($element);
  }

  /**
   * Tests that an error is thrown if a lazy builder doesn't return an array.
   */
  public function testNonArrayReturnFromLazyBuilder(): void {
    $element = [
      '#lazy_builder' => ['\Drupal\Tests\Core\Render\PlaceholdersTest::callbackNonArrayReturn', []],
    ];
    $this->expectException('AssertionError');
    $this->expectExceptionMessage("#lazy_builder callbacks must return a valid renderable array, got boolean from \Drupal\Tests\Core\Render\PlaceholdersTest::callbackNonArrayReturn");
    $this->renderer->renderRoot($element);
  }

  /**
   * Tests the creation of an element with a child and a sub-child.
   *
   * Each element has the same #lazy_builder callback, but with different
   * contexts. They don't modify markup, only attach additional drupalSettings.
   *
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers ::replacePlaceholders
   */
  public function testRenderChildrenPlaceholdersDifferentArguments(): void {
    $this->setUpRequest();
    $this->setupMemoryCache();
    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnArgument(0);
    $this->callableResolver->expects($this->any())
      ->method('getCallableFromDefinition')
      ->willReturnArgument(0);
    $this->setupThemeManagerForDetails();

    $args_1 = ['foo', TRUE];
    $args_2 = ['bar', TRUE];
    $args_3 = ['baz', TRUE];
    $test_element = $this->generatePlaceholdersWithChildrenTestElement($args_1, $args_2, $args_3);

    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $expected_output = <<<HTML
<details>
  <summary>Parent</summary>
  <div class="details-wrapper"><details>
  <summary>Child</summary>
  <div class="details-wrapper">Subchild</div>
</details></div>
</details>
HTML;
    $this->assertSame($expected_output, (string) $output, 'Output is not overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame($expected_output, (string) $element['#markup'], '#markup is not overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'dynamic_animal' => [$args_1[0] => TRUE, $args_2[0] => TRUE, $args_3[0] => TRUE],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each placeholder #lazy_builder callback exist.');

    // GET request: validate cached data.
    $cached_element = $this->memoryCache->get(['test', 'renderer', 'children_placeholders'], CacheableMetadata::createFromRenderArray($element))->data;
    $expected_element = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
        'placeholders' => [
          'parent-x-parent' => [
            '#lazy_builder' => [__NAMESPACE__ . '\\PlaceholdersTest::callback', $args_1],
          ],
          'child-x-child' => [
            '#lazy_builder' => [__NAMESPACE__ . '\\PlaceholdersTest::callback', $args_2],
          ],
          'subchild-x-subchild' => [
            '#lazy_builder' => [__NAMESPACE__ . '\\PlaceholdersTest::callback', $args_3],
          ],
        ],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];

    // Verify that the correct data is cached: the stored #markup is not
    // affected by placeholder #lazy_builder callbacks.
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $this->assertNotEmpty($xpath->query('//details/summary[text()="Parent"]')->length);
    $this->assertNotEmpty($xpath->query('//details/div[@class="details-wrapper"]/details/summary[text()="Child"]')->length);
    $this->assertNotEmpty($xpath->query('//details/div[@class="details-wrapper"]/details/div[@class="details-wrapper" and text()="Subchild"]')->length);

    // Remove markup because it's compared above in the xpath.
    unset($cached_element['#markup']);
    $this->assertEquals($cached_element, $expected_element, 'The correct data is cached: the stored #attached properties are not affected by placeholder #lazy_builder callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($expected_output, (string) $output, 'Output is not overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each placeholder #lazy_builder callback exist.');

    // Use the exact same element, but now unset #cache; ensure we get the same
    // result.
    unset($test_element['#cache']);
    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($expected_output, (string) $output, 'Output is not overridden.');
    $this->assertSame($expected_output, (string) $element['#markup'], '#markup is not overridden.');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each #lazy_builder callback exist.');
  }

  /**
   * Tests the creation of an element with a lazy_builder_preview.
   *
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers ::replacePlaceholders
   */
  public function testRenderLazyBuilderPreview(): void {
    $this->setUpRequest();
    $this->setupMemoryCache();
    $this->renderCache = new TestPlaceholderingRenderCache($this->requestStack, $this->cacheFactory, $this->cacheContextsManager, $this->placeholderGenerator);
    $this->renderer = new Renderer($this->callableResolver, $this->themeManager, $this->elementInfo, $this->placeholderGenerator, $this->renderCache, $this->requestStack, $this->rendererConfig);

    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnArgument(0);
    $this->callableResolver->expects($this->any())
      ->method('getCallableFromDefinition')
      ->willReturnArgument(0);

    $test_element = $this->generatePlaceholderWithLazyBuilderPreview();

    $element1 = $element2 = $test_element;
    // Render the element twice so that it is in the render cache.
    $result = $this->renderer->renderRoot($element1);
    $result = $this->renderer->renderRoot($element2);
    $placeholder_string = (string) $this->renderCache->placeholderElements[0]['#markup'];
    $this->assertSame($this->renderCache->placeholderElements[0]['#attached']['placeholders'][$placeholder_string]['#preview'], ['#markup' => 'Lazy Builder Preview']);
  }

  /**
   * Generates an element with a lazy builder and preview.
   */
  public function generatePlaceholderWithLazyBuilderPreview(): array {
    return [
      '#cache' => [
        'keys' => ['test_render'],
      ],
      '#lazy_builder' => [__namespace__ . '\\PlaceholdersTest::callbackPerUser', ['foo']],
      '#lazy_builder_preview' => ['#markup' => 'Lazy Builder Preview'],
    ];
  }

  /**
   * Generates an element with placeholders at 3 levels.
   *
   * @param array $args_1
   *   The arguments for the placeholder at level 1.
   * @param array $args_2
   *   The arguments for the placeholder at level 2.
   * @param array $args_3
   *   The arguments for the placeholder at level 3.
   *
   * @return array
   *   The generated render array for testing.
   */
  protected function generatePlaceholdersWithChildrenTestElement(array $args_1, array $args_2, array $args_3) {
    $test_element = [
      '#type' => 'details',
      '#cache' => [
        'keys' => ['test', 'renderer', 'children_placeholders'],
      ],
      '#title' => 'Parent',
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
        'placeholders' => [
          'parent-x-parent' => [
            '#lazy_builder' => [__NAMESPACE__ . '\\PlaceholdersTest::callback', $args_1],
          ],
        ],
      ],
    ];
    $test_element['child'] = [
      '#type' => 'details',
      '#attached' => [
        'placeholders' => [
          'child-x-child' => [
            '#lazy_builder' => [__NAMESPACE__ . '\\PlaceholdersTest::callback', $args_2],
          ],
        ],
      ],
      '#title' => 'Child',
    ];
    $test_element['child']['subchild'] = [
      '#attached' => [
        'placeholders' => [
          'subchild-x-subchild' => [
            '#lazy_builder' => [__NAMESPACE__ . '\\PlaceholdersTest::callback', $args_3],
          ],
        ],
      ],
      '#markup' => 'Subchild',
    ];
    return $test_element;
  }

  /**
   * @return \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\Builder\InvocationMocker
   */
  protected function setupThemeManagerForDetails() {
    return $this->themeManager->expects($this->any())
      ->method('render')
      ->willReturnCallback(function ($theme, array $vars) {
        $output = <<<'EOS'
<details>
  <summary>{{ title }}</summary>
  <div class="details-wrapper">{{ children }}</div>
</details>
EOS;
        $output = str_replace([
          '{{ title }}',
          '{{ children }}',
        ], [$vars['#title'], $vars['#children']], $output);
        return $output;
      });
  }

}

/**
 * @see \Drupal\Tests\Core\Render\RendererPlaceholdersTest::testRecursivePlaceholder()
 */
class RecursivePlaceholdersTest implements TrustedCallbackInterface {

  /**
   * #lazy_builder callback; bubbles another placeholder.
   *
   * @param string $animal
   *   An animal.
   *
   * @return array
   *   A renderable array.
   */
  public static function callback($animal) {
    return [
      'another' => [
        '#create_placeholder' => TRUE,
        '#lazy_builder' => [PlaceholdersTest::class . '::callback', [$animal]],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['callback'];
  }

}

class TestPlaceholderingRenderCache extends PlaceholderingRenderCache {

  /**
   * The placeholder elements created during rendering.
   */
  public array $placeholderElements = [];

  protected function createPlaceholderAndRemember(array $rendered_elements, array $pre_bubbling_elements) {
    $placeholder_element = parent::createPlaceholderAndRemember($rendered_elements, $pre_bubbling_elements);
    $this->placeholderElements[] = $placeholder_element;
    return $placeholder_element;
  }

}
