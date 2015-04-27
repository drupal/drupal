<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\RendererPostRenderCacheTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Cache\Cache;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 */
class RendererPostRenderCacheTest extends RendererTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Disable the required cache contexts, so that this test can test just the
    // #post_render_cache behavior.
    $this->rendererConfig['required_cache_contexts'] = [];

    parent::setUp();
  }

  /**
   * Generates an element with a #post_render_cache callback.
   *
   * @return array
   *   An array containing:
   *   - A render array containing a #post_render_cache callback.
   *   - The context used for that #post_render_cache callback.
   */
  protected function generatePostRenderCacheElement() {
    $context = ['foo' => $this->randomContextValue()];
    $test_element = [];
    $test_element['#markup'] = '';
    $test_element['#attached']['drupalSettings']['foo'] = 'bar';
    $test_element['#post_render_cache']['Drupal\Tests\Core\Render\PostRenderCache::callback'] = [
      $context
    ];

    return [$test_element, $context];
  }

  /**
   * @covers ::render
   * @covers ::doRender
   */
  public function testPostRenderCacheWithCacheDisabled() {
    list($element, $context) = $this->generatePostRenderCacheElement();
    $this->setUpUnusedCache();

    // #cache disabled.
    $element['#markup'] = '<p>#cache disabled</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertSame($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'common_test' => $context,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the #post_render_cache callback exist.');
  }

  /**
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers \Drupal\Core\Render\RenderCache::set
   * @covers \Drupal\Core\Render\RenderCache::createCacheID
   */
  public function testPostRenderCacheWithColdCache() {
    list($test_element, $context) = $this->generatePostRenderCacheElement();
    $element = $test_element;
    $this->setupMemoryCache();

    $this->setUpRequest('GET');

    // GET request: #cache enabled, cache miss.
    $element['#cache'] = ['keys' => ['post_render_cache_test_GET']];
    $element['#markup'] = '<p>#cache enabled, GET</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'common_test' => $context,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the #post_render_cache callback exist.');

    // GET request: validate cached data.
    $cached_element = $this->memoryCache->get('post_render_cache_test_GET')->data;
    $expected_element = [
      '#markup' => '<p>#cache enabled, GET</p>',
      '#attached' => $test_element['#attached'],
      '#post_render_cache' => $test_element['#post_render_cache'],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    $this->assertSame($cached_element, $expected_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $element['#cache'] = ['keys' => ['post_render_cache_test_GET']];
    $element['#markup'] = '<p>#cache enabled, GET</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'common_test' => $context,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the #post_render_cache callback exist.');
  }

  /**
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers ::processPostRenderCache
   */
  public function testPostRenderCacheWithPostRequest() {
    list($test_element, $context) = $this->generatePostRenderCacheElement();
    $this->setUpUnusedCache();

    // Verify behavior when handling a non-GET request, e.g. a POST request:
    // also in that case, #post_render_cache callbacks must be called.
    $this->setUpRequest('POST');

    // POST request: #cache enabled, cache miss.
    $element = $test_element;
    $element['#cache'] = ['keys' => ['post_render_cache_test_POST']];
    $element['#markup'] = '<p>#cache enabled, POST</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'common_test' => $context,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the #post_render_cache callback exist.');
  }

  /**
   * Tests a #post_render_cache callback that adds another #post_render_cache
   * callback.
   *
   * E.g. when rendering a node in a #post_render_cache callback, the rendering
   * of that node needs a #post_render_cache callback of its own to be executed
   * (to render the node links).
   *
   * @covers ::render
   * @covers ::doRender
   * @covers ::processPostRenderCache
   */
  public function testRenderRecursivePostRenderCache() {
    $context = ['foo' => $this->randomContextValue()];
    $element = [];
    $element['#markup'] = '';

    $element['#post_render_cache']['Drupal\Tests\Core\Render\PostRenderCacheRecursion::callback'] = [
      $context
    ];

    $output = $this->renderer->renderRoot($element);
    $this->assertEquals('<p>overridden</p>', $output, 'The output has been modified by the indirect, recursive #post_render_cache callback.');
    $this->assertSame($element['#markup'], '<p>overridden</p>', '#markup is overridden by the indirect, recursive #post_render_cache callback.');
    $expected_js_settings = [
      'common_test' => $context,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified by the indirect, recursive #post_render_cache callback.');
  }

  /**
   * Create an element with a child and subchild. Each element has the same
   * #post_render_cache callback, but with different contexts.
   *
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers ::processPostRenderCache
   */
  public function testRenderChildrenPostRenderCacheDifferentContexts() {
    $this->setUpRequest();
    $this->setupMemoryCache();
    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnArgument(0);
    $this->elementInfo->expects($this->any())
      ->method('getInfo')
      ->with('details')
      ->willReturn(['#theme_wrappers' => ['details']]);
    $this->controllerResolver->expects($this->any())
      ->method('getControllerFromDefinition')
      ->willReturnArgument(0);
    $this->setupThemeManagerForDetails();

    $context_1 = ['foo' => $this->randomContextValue()];
    $context_2 = ['bar' => $this->randomContextValue()];
    $context_3 = ['baz' => $this->randomContextValue()];
    $test_element = $this->generatePostRenderCacheWithChildrenTestElement($context_1, $context_2, $context_3);

    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'common_test' => $context_1 + $context_2 + $context_3,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // GET request: validate cached data.
    $cached_element = $this->memoryCache->get('simpletest:drupal_render:children_post_render_cache')->data;
    $expected_element = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
      '#post_render_cache' => [
        'Drupal\Tests\Core\Render\PostRenderCache::callback' => [
          $context_1,
          $context_2,
          $context_3,
        ]
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];

    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $parent = $xpath->query('//details/summary[text()="Parent"]')->length;
    $child =  $xpath->query('//details/div[@class="details-wrapper"]/details/summary[text()="Child"]')->length;
    $subchild = $xpath->query('//details/div[@class="details-wrapper"]/details/div[@class="details-wrapper" and text()="Subchild"]')->length;
    $this->assertTrue($parent && $child && $subchild, 'The correct data is cached: the stored #markup is not affected by #post_render_cache callbacks.');

    // Remove markup because it's compared above in the xpath.
    unset($cached_element['#markup']);
    $this->assertEquals($cached_element, $expected_element, 'The correct data is cached: the stored #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // Use the exact same element, but now unset #cache; ensure we get the same
    // result.
    unset($test_element['#cache']);
    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertSame($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');
  }

  /**
   * Create an element with a child and subchild. Each element has the same
   * #post_render_cache callback, but with different contexts. Both the
   * parent and the child elements have #cache set. The cached parent element
   * must contain the pristine child element, i.e. unaffected by its
   * #post_render_cache callbacks. I.e. the #post_render_cache callbacks may
   * not yet have run, or otherwise the cached parent element would contain
   * personalized data, thereby breaking the render cache.
   *
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers ::processPostRenderCache
   */
  public function testRenderChildrenPostRenderCacheComplex() {
    $this->setUpRequest();
    $this->setupMemoryCache();
    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnArgument(0);
    $this->elementInfo->expects($this->any())
      ->method('getInfo')
      ->with('details')
      ->willReturn(['#theme_wrappers' => ['details']]);
    $this->setupThemeManagerForDetails();

    $context_1 = ['foo' => $this->randomContextValue()];
    $context_2 = ['bar' => $this->randomContextValue()];
    $context_3 = ['baz' => $this->randomContextValue()];
    $test_element = $this->generatePostRenderCacheWithChildrenTestElement($context_1, $context_2, $context_3);

    $expected_js_settings = [
      'foo' => 'bar',
      'common_test' => $context_1 + $context_2 + $context_3,
    ];

    $element = $test_element;
    $element['#cache']['keys'] = ['simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_parent'];
    $element['child']['#cache']['keys'] = ['simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_child'];
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // GET request: validate cached data for both the parent and child.
    $cached_parent_element = $this->memoryCache->get('simpletest:drupal_render:children_post_render_cache:nested_cache_parent')->data;
    $cached_child_element = $this->memoryCache->get('simpletest:drupal_render:children_post_render_cache:nested_cache_child')->data;
    $expected_parent_element = [
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
      '#post_render_cache' => [
        'Drupal\Tests\Core\Render\PostRenderCache::callback' => [
          $context_1,
          $context_2,
          $context_3,
        ]
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];

    $dom = Html::load($cached_parent_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $parent = $xpath->query('//details/summary[text()="Parent"]')->length;
    $child =  $xpath->query('//details/div[@class="details-wrapper"]/details/summary[text()="Child"]')->length;
    $subchild = $xpath->query('//details/div[@class="details-wrapper"]/details/div [@class="details-wrapper" and text()="Subchild"]')->length;
    $this->assertTrue($parent && $child && $subchild, 'The correct data is cached for the parent: the stored #markup is not affected by #post_render_cache callbacks.');

    // Remove markup because it's compared above in the xpath.
    unset($cached_parent_element['#markup']);
    $this->assertEquals($cached_parent_element, $expected_parent_element, 'The correct data is cached for the parent: the stored #attached properties are not affected by #post_render_cache callbacks.');

    $expected_child_element = [
      '#attached' => [
      ],
      '#post_render_cache' => [
        'Drupal\Tests\Core\Render\PostRenderCache::callback' => [
          $context_2,
          $context_3,
        ]
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];

    $dom = Html::load($cached_child_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $child =  $xpath->query('//details/summary[text()="Child"]')->length;
    $subchild = $xpath->query('//details/div [@class="details-wrapper" and text()="Subchild"]')->length;
    $this->assertTrue($child && $subchild, 'The correct data is cached for the child: the stored #markup is not affected by #post_render_cache callbacks.');

    // Remove markup because it's compared above in the xpath.
    unset($cached_child_element['#markup']);
    $this->assertEquals($cached_child_element, $expected_child_element, 'The correct data is cached for the child: the stored #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit, parent element.
    $element = $test_element;
    $element['#cache']['keys'] = ['simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_parent'];
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // GET request: #cache enabled, cache hit, child element.
    $element = $test_element;
    $element['child']['#cache']['keys'] = ['simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_child'];
    $element = $element['child'];
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $expected_js_settings = [
      'common_test' => $context_2 + $context_3,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');
  }

  /**
   * Tests #post_render_cache placeholders.
   *
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers ::processPostRenderCache
   * @covers ::generateCachePlaceholder
   */
  public function testPlaceholder() {
    $this->setupMemoryCache();

    $context = [
      'bar' => $this->randomContextValue(),
      // Provide a token instead of letting one be generated by
      // RendererInterface::generateCachePlaceholder(), otherwise we cannot know
      // what the token is.
      'token' => \Drupal\Component\Utility\Crypt::randomBytesBase64(55),
    ];
    $callback =  __NAMESPACE__ . '\\PostRenderCache::placeholder';
    $placeholder = \Drupal::service('renderer')->generateCachePlaceholder($callback, $context);
    $this->assertSame($placeholder, Html::normalize($placeholder), 'Placeholder unaltered by Html::normalize() which is used by FilterHtmlCorrector.');

    $test_element = [
      '#post_render_cache' => [
        $callback => [
          $context
        ],
      ],
      '#markup' => $placeholder,
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
    ];
    $expected_output = '<pre><bar>' . $context['bar'] . '</bar></pre>';

    // #cache disabled.
    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, $expected_output, 'Placeholder was replaced in output');
    $expected_js_settings = [
      'common_test' => $context,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; JavaScript setting is added to page.');

    // GET request: #cache enabled, cache miss.
    $this->setUpRequest();
    $element = $test_element;
    $element['#cache'] = ['keys' => ['render_cache_placeholder_test_GET']];
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $this->assertSame($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; JavaScript setting is added to page.');

    // GET request: validate cached data.
    $expected_token = $context['token'];
    $cached_element = $this->memoryCache->get('render_cache_placeholder_test_GET')->data;
    // Parse unique token out of the cached markup.
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $this->assertEquals(1, $nodes->length, 'The token attribute was found in the cached markup');
    $token = '';
    if ($nodes->length) {
      $token = $nodes->item(0)->getAttribute('token');
    }
    $this->assertSame($token, $expected_token, 'The tokens are identical');
    // Verify the token is in the cached element.
    $expected_element = [
      '#markup' => '<pre><drupal-render-cache-placeholder callback="' . $callback . '" token="'. $expected_token . '"></drupal-render-cache-placeholder></pre>',
      '#attached' => [],
      '#post_render_cache' => [
        $callback => [
          $context
        ],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    $this->assertSame($cached_element, $expected_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $element['#cache'] = ['keys' => ['render_cache_placeholder_test_GET']];
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; JavaScript setting is added to page.');
  }

  /**
   * Tests child element that uses #post_render_cache but that is rendered via a
   * template.
   */
  public function testChildElementPlaceholder() {
    $this->setupMemoryCache();
    // Simulate the theme system/Twig: a recursive call to Renderer::render(),
    // just like the theme system or a Twig template would have done.
    $this->themeManager->expects($this->any())
      ->method('render')
      ->willReturnCallback(function ($hook, $vars) {
        return $this->renderer->render($vars['foo']) . "\n";
      });

    $context = [
      'bar' => $this->randomContextValue(),
      // Provide a token instead of letting one be generated by
      // drupal_render_cache_generate_placeholder(), otherwise we cannot know
      // what the token is.
      'token' => \Drupal\Component\Utility\Crypt::randomBytesBase64(55),
    ];
    $callback =  __NAMESPACE__ . '\\PostRenderCache::placeholder';
    $placeholder = \Drupal::service('renderer')->generateCachePlaceholder($callback, $context);
    $test_element = [
      '#theme' => 'some_theme_function',
      'foo' => [
        '#post_render_cache' => [
          $callback => [
            $context
          ],
        ],
        '#markup' => $placeholder,
        '#prefix' => '<pre>',
        '#suffix' => '</pre>'
      ],
    ];
    $expected_output = '<pre><bar>' . $context['bar'] . '</bar></pre>' . "\n";

    // #cache disabled.
    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, $expected_output, 'Placeholder was replaced in output');
    $expected_js_settings = [
      'common_test' => $context,
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; JavaScript setting is added to page.');

    // GET request: #cache enabled, cache miss.
    $this->setUpRequest();
    $element = $test_element;
    $element['#cache'] = ['keys' => ['render_cache_placeholder_test_GET']];
    $element['foo']['#cache'] = ['keys' => ['render_cache_placeholder_test_child_GET']];
    // Render, which will use the common-test-render-element.html.twig template.
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; JavaScript setting is added to page.');

    // GET request: validate cached data for child element.
    $expected_token = $context['token'];
    $cached_element = $this->memoryCache->get('render_cache_placeholder_test_child_GET')->data;
    // Parse unique token out of the cached markup.
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $this->assertEquals(1, $nodes->length, 'The token attribute was found in the cached child element markup');
    $token = '';
    if ($nodes->length) {
      $token = $nodes->item(0)->getAttribute('token');
    }
    $this->assertSame($token, $expected_token, 'The tokens are identical for the child element');
    // Verify the token is in the cached element.
    $expected_element = [
      '#markup' => '<pre><drupal-render-cache-placeholder callback="' . $callback . '" token="'. $expected_token . '"></drupal-render-cache-placeholder></pre>',
      '#attached' => [],
      '#post_render_cache' => [
        $callback => [
          $context,
        ],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    $this->assertSame($cached_element, $expected_element, 'The correct data is cached for the child element: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: validate cached data (for the parent/entire render array).
    $cached_element = $this->memoryCache->get('render_cache_placeholder_test_GET')->data;
    // Parse unique token out of the cached markup.
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $this->assertEquals(1, $nodes->length, 'The token attribute was found in the cached parent element markup');
    $token = '';
    if ($nodes->length) {
      $token = $nodes->item(0)->getAttribute('token');
    }
    $this->assertSame($token, $expected_token, 'The tokens are identical for the parent element');
    // Verify the token is in the cached element.
    $expected_element = [
      '#markup' => '<pre><drupal-render-cache-placeholder callback="' . $callback . '" token="'. $expected_token . '"></drupal-render-cache-placeholder></pre>' . "\n",
      '#attached' => [],
      '#post_render_cache' => [
        $callback => [
          $context,
        ],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    $this->assertSame($cached_element, $expected_element, 'The correct data is cached for the parent element: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: validate cached data.
    // Check the cache of the child element again after the parent has been
    // rendered.
    $cached_element = $this->memoryCache->get('render_cache_placeholder_test_child_GET')->data;
    // Verify that the child element contains the correct
    // render_cache_placeholder markup.
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $this->assertEquals(1, $nodes->length, 'The token attribute was found in the cached child element markup');
    $token = '';
    if ($nodes->length) {
      $token = $nodes->item(0)->getAttribute('token');
    }
    $this->assertSame($token, $expected_token, 'The tokens are identical for the child element');
    // Verify the token is in the cached element.
    $expected_element = [
      '#markup' => '<pre><drupal-render-cache-placeholder callback="' . $callback . '" token="'. $expected_token . '"></drupal-render-cache-placeholder></pre>',
      '#attached' => [],
      '#post_render_cache' => [
        $callback => [
          $context,
        ],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    $this->assertSame($cached_element, $expected_element, 'The correct data is cached for the child element: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $element['#cache'] = ['keys' => ['render_cache_placeholder_test_GET']];
    // Render, which will use the common-test-render-element.html.twig template.
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; JavaScript setting is added to page.');
  }

  /**
   * Generates an element with a #post_render_cache callback at 3 levels.
   *
   * @param array $context_1
   *   The context for the #post_render_cache calback at level 1.
   * @param array $context_2
   *   The context for the #post_render_cache calback at level 2.
   * @param array $context_3
   *   The context for the #post_render_cache calback at level 3.
   *
   * @return array
   *   The generated render array for testing.
   */
  protected function generatePostRenderCacheWithChildrenTestElement(array $context_1, array $context_2, array $context_3) {
    $test_element = [
      '#type' => 'details',
      '#cache' => [
        'keys' => ['simpletest', 'drupal_render', 'children_post_render_cache'],
      ],
      '#post_render_cache' => [
        __NAMESPACE__ . '\\PostRenderCache::callback' => [$context_1]
      ],
      '#title' => 'Parent',
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
      ],
    ];
    $test_element['child'] = [
      '#type' => 'details',
      '#post_render_cache' => [
        __NAMESPACE__ . '\\PostRenderCache::callback' => [$context_2],
      ],
      '#title' => 'Child',
    ];
    $test_element['child']['subchild'] = [
      '#post_render_cache' => [
        __NAMESPACE__ . '\\PostRenderCache::callback' => [$context_3]
      ],
      '#markup' => 'Subchild',
    ];
    return $test_element;
  }

  /**
   * @return \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit_Framework_MockObject_Builder_InvocationMocker
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
          '{{ children }}'
        ], [$vars['#title'], $vars['#children']], $output);
        return $output;
      });
  }

}

class PostRenderCacheRecursion {

  /**
   * #post_render_cache callback; bubbles another #post_render_cache callback.
   *
   * @param array $element
   *  A render array with the following keys:
   *    - #markup
   *    - #attached
   * @param array $context
   *  An array with the following keys:
   *    - foo: contains a random string.
   *
   * @return array $element
   *   The updated $element.
   */
  public static function callback(array $element, array $context) {
    // Render a child which itself also has a #post_render_cache callback that
    // must be bubbled.
    $child = [];
    $child['#markup'] = 'foo';
    $child['#post_render_cache']['Drupal\Tests\Core\Render\PostRenderCache::callback'][] = $context;

    // Render the child.
    $element['#markup'] = \Drupal::service('renderer')->render($child);

    return $element;
  }

}
