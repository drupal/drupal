<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\RendererPlaceholdersTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Cache\Cache;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 */
class RendererPlaceholdersTest extends RendererTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Disable the required cache contexts, so that this test can test just the
    // placeholder replacement behavior.
    $this->rendererConfig['required_cache_contexts'] = [];

    parent::setUp();
  }

  /**
   * Provides the two classes of placeholders: cacheable and uncacheable.
   *
   * i.e. with or without #cache[keys].
   *
   * Also, different types:
   * - A) automatically generated placeholder
   *   - 1) manually triggered (#create_placeholder = TRUE)
   *   - 2) automatically triggered (based on max-age = 0 in its subtree)
   * - B) manually generated placeholder
   *
   * So, in total 2*3 = 6 permutations.
   *
   * @todo Case A2 is not yet supported by core. So that makes for only 4
   *   permutations currently.
   *
   * @return array
   */
  public function providerPlaceholders() {
    $args = [$this->randomContextValue()];

    $generate_placeholder_markup = function($cache_keys = NULL) use ($args) {
      $token_render_array = [
        '#cache' => [],
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
      ];
      if (is_array($cache_keys)) {
        $token_render_array['#cache']['keys'] = $cache_keys;
      }
      else {
        unset($token_render_array['#cache']);
      }
      $token = hash('sha1', serialize($token_render_array));
      return '<drupal-render-placeholder callback="Drupal\Tests\Core\Render\PlaceholdersTest::callback" arguments="0=' . $args[0] . '" token="' . $token . '"></drupal-render-placeholder>';
    };

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
    $base_element_a2 = [
      // @todo, see docblock
    ];
    $base_element_b = [
      '#markup' => $generate_placeholder_markup(),
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
        'placeholders' => [
          $generate_placeholder_markup() => [
            '#cache' => [],
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
    $cases[] = [
      $element_without_cache_keys,
      $args,
      FALSE,
      [],
    ];

    // Case two: render array that has a placeholder that is:
    // - automatically created, but manually triggered (#create_placeholder = TRUE)
    // - cacheable
    $element_with_cache_keys = $base_element_a1;
    $element_with_cache_keys['placeholder']['#cache']['keys'] = $keys;
    $cases[] = [
      $element_with_cache_keys,
      $args,
      $keys,
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
    // - manually created
    // - uncacheable
    $x = $base_element_b;
    unset($x['#attached']['placeholders'][$generate_placeholder_markup()]['#cache']);
    $cases[] = [
      $x,
      $args,
      FALSE,
      [],
    ];

    // Case four: render array that has a placeholder that is:
    // - manually created
    // - cacheable
    $x = $base_element_b;
    $x['#markup'] = $generate_placeholder_markup($keys);
    $x['#attached']['placeholders'] = [
      $generate_placeholder_markup($keys) => [
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args],
        '#cache' => ['keys' => $keys],
      ],
    ];
    $cases[] = [
      $x,
      $args,
      $keys,
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
    $args = [$this->randomContextValue()];
    $test_element = [];
    $test_element['#attached']['drupalSettings']['foo'] = 'bar';
    $test_element['placeholder']['#cache']['keys'] = ['placeholder', 'output', 'can', 'be', 'render', 'cached', 'too'];
    $test_element['placeholder']['#cache']['contexts'] = [];
    $test_element['placeholder']['#create_placeholder'] = TRUE;
    $test_element['placeholder']['#lazy_builder'] = ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args];

    return [$test_element, $args];
  }

  /**
   * @param FALSE|array $cid_parts
   * @param array $expected_data
   *   FALSE if no render cache item is expected, a render array with the
   *   expected values if a render cache item is expected.
   */
  protected function assertPlaceholderRenderCache($cid_parts, array $expected_data) {
    if ($cid_parts !== FALSE) {
      // Verify render cached placeholder.
      $cached_element = $this->memoryCache->get(implode(':', $cid_parts))->data;
      $this->assertSame($expected_data, $cached_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by the placeholder being replaced.');
    }
  }
  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @dataProvider providerPlaceholders
   */
  public function testUncacheableParent($element, $args, $placeholder_cid_keys, array $placeholder_expected_render_cache_array) {
    if ($placeholder_cid_keys) {
      $this->setupMemoryCache();
    }
    else {
      $this->setUpUnusedCache();
    }

    $this->setUpRequest('GET');

    // No #cache on parent element.
    $element['#prefix'] = '<p>#cache disabled</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame('<p>#cache disabled</p><p>This is a rendered placeholder!</p>', $output, 'Output is overridden.');
    $this->assertSame('<p>#cache disabled</p><p>This is a rendered placeholder!</p>', $element['#markup'], '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'dynamic_animal' => $args[0],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the placeholder #lazy_builder callback exist.');
    $this->assertPlaceholderRenderCache($placeholder_cid_keys, $placeholder_expected_render_cache_array);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers \Drupal\Core\Render\RenderCache::set
   * @covers \Drupal\Core\Render\RenderCache::createCacheID
   *
   * @dataProvider providerPlaceholders
   */
  public function testCacheableParent($test_element, $args, $placeholder_cid_keys, array $placeholder_expected_render_cache_array) {
    $element = $test_element;
    $this->setupMemoryCache();

    $this->setUpRequest('GET');

    // Generate the expected placeholder render array, so that we can generate
    // the expected placeholder markup.
    $expected_placeholder_render_array = [];
    // When there was a child element that created a placeholder, the Renderer
    // automatically initializes #cache[contexts].
    if (Element::children($test_element)) {
      $expected_placeholder_render_array['#cache']['contexts'] = [];
    }
    // When the placeholder itself is cacheable, its cache keys are present.
    if ($placeholder_cid_keys) {
      $expected_placeholder_render_array['#cache']['keys'] = $placeholder_cid_keys;
    }
    $expected_placeholder_render_array['#lazy_builder'] = ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', $args];
    $token = hash('sha1', serialize($expected_placeholder_render_array));
    $expected_placeholder_markup = '<drupal-render-placeholder callback="Drupal\Tests\Core\Render\PlaceholdersTest::callback" arguments="0=' . $args[0] . '" token="' . $token . '"></drupal-render-placeholder>';
    $this->assertSame($expected_placeholder_markup, Html::normalize($expected_placeholder_markup), 'Placeholder unaltered by Html::normalize() which is used by FilterHtmlCorrector.');

    // GET request: #cache enabled, cache miss.
    $element['#cache'] = ['keys' => ['placeholder_test_GET']];
    $element['#prefix'] = '<p>#cache enabled, GET</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame('<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>', $output, 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame('<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>', $element['#markup'], '#markup is overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'dynamic_animal' => $args[0],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the one added by the placeholder #lazy_builder callback exist.');
    $this->assertPlaceholderRenderCache($placeholder_cid_keys, $placeholder_expected_render_cache_array);

    // GET request: validate cached data.
    $cached_element = $this->memoryCache->get('placeholder_test_GET')->data;
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
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    $expected_element['#attached']['placeholders'][$expected_placeholder_markup] = $expected_placeholder_render_array;
    $this->assertEquals($cached_element, $expected_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by placeholder #lazy_builder callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $element['#cache'] = ['keys' => ['placeholder_test_GET']];
    $element['#prefix'] = '<p>#cache enabled, GET</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame('<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>', $output, 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame('<p>#cache enabled, GET</p><p>This is a rendered placeholder!</p>', $element['#markup'], '#markup is overridden.');
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
  public function testCacheableParentWithPostRequest($test_element, $args) {
    $this->setUpUnusedCache();

    // Verify behavior when handling a non-GET request, e.g. a POST request:
    // also in that case, placeholders must be replaced.
    $this->setUpRequest('POST');

    // POST request: #cache enabled, cache miss.
    $element = $test_element;
    $element['#cache'] = ['keys' => ['placeholder_test_POST']];
    $element['#prefix'] = '<p>#cache enabled, POST</p>';
    $output = $this->renderer->renderRoot($element);
    $this->assertSame('<p>#cache enabled, POST</p><p>This is a rendered placeholder!</p>', $output, 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame('<p>#cache enabled, POST</p><p>This is a rendered placeholder!</p>', $element['#markup'], '#markup is overridden.');
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
   * Tests a placeholder that adds another placeholder.
   *
   * E.g. when rendering a node in a placeholder the rendering of that node
   * needs a placeholder of its own to be executed (to render the node links).
   *
   * @covers ::render
   * @covers ::doRender
   * @covers ::replacePlaceholders
   */
  public function testRecursivePlaceholder() {
    $args = [$this->randomContextValue()];
    $element = [];
    $element['#create_placeholder'] = TRUE;
    $element['#lazy_builder'] = ['Drupal\Tests\Core\Render\RecursivePlaceholdersTest::callback', $args];

    $output = $this->renderer->renderRoot($element);
    $this->assertEquals('<p>This is a rendered placeholder!</p>', $output, 'The output has been modified by the indirect, recursive placeholder #lazy_builder callback.');
    $this->assertSame($element['#markup'], '<p>This is a rendered placeholder!</p>', '#markup is overridden by the indirect, recursive placeholder #lazy_builder callback.');
    $expected_js_settings = [
      'dynamic_animal' => $args[0],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified by the indirect, recursive placeholder #lazy_builder callback.');
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @expectedException \DomainException
   * @expectedExceptionMessage The #lazy_builder property must have an array as a value.
   */
  public function testInvalidLazyBuilder() {
    $element = [];
    $element['#lazy_builder'] = '\Drupal\Tests\Core\Render\PlaceholdersTest::callback';

    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @expectedException \DomainException
   * @expectedExceptionMessage The #lazy_builder property must have an array as a value, containing two values: the callback, and the arguments for the callback.
   */
  public function testInvalidLazyBuilderArguments() {
    $element = [];
    $element['#lazy_builder'] = ['\Drupal\Tests\Core\Render\PlaceholdersTest::callback', 'arg1', 'arg2'];

    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @see testNonScalarLazybuilderCallbackContext
   */
  public function testScalarLazybuilderCallbackContext() {
    $element = [];
    $element['#lazy_builder'] = ['\Drupal\Tests\Core\Render\PlaceholdersTest::callback', [
      'string' => 'foo',
      'bool' => TRUE,
      'int' => 1337,
      'float' => 3.14,
      'null' => NULL,
    ]];

    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @expectedException \DomainException
   * @expectedExceptionMessage A #lazy_builder callback's context may only contain scalar values or NULL.
   */
  public function testNonScalarLazybuilderCallbackContext() {
    $element = [];
    $element['#lazy_builder'] = ['\Drupal\Tests\Core\Render\PlaceholdersTest::callback', [
      'string' => 'foo',
      'bool' => TRUE,
      'int' => 1337,
      'float' => 3.14,
      'null' => NULL,
      // array is not one of the scalar types.
      'array' => ['hi!'],
    ]];

    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @expectedException \DomainException
   * @expectedExceptionMessage When a #lazy_builder callback is specified, no children can exist; all children must be generated by the #lazy_builder callback. You specified the following children: child_a, child_b.
   */
  public function testChildrenPlusBuilder() {
    $element = [];
    $element['#lazy_builder'] = ['Drupal\Tests\Core\Render\RecursivePlaceholdersTest::callback', []];
    $element['child_a']['#markup'] = 'Oh hai!';
    $element['child_b']['#markup'] = 'kthxbai';

    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @expectedException \DomainException
   * @expectedExceptionMessage When a #lazy_builder callback is specified, no properties can exist; all properties must be generated by the #lazy_builder callback. You specified the following properties: #llama, #piglet.
   */
  public function testPropertiesPlusBuilder() {
    $element = [];
    $element['#lazy_builder'] = ['Drupal\Tests\Core\Render\RecursivePlaceholdersTest::callback', []];
    $element['#llama'] = '#awesome';
    $element['#piglet'] = '#cute';

    $this->renderer->renderRoot($element);
  }

  /**
   * @covers ::render
   * @covers ::doRender
   *
   * @expectedException \LogicException
   * @expectedExceptionMessage When #create_placeholder is set, a #lazy_builder callback must be present as well.
   */
  public function testCreatePlaceholderPropertyWithoutLazyBuilder() {
    $element = [];
    $element['#create_placeholder'] = TRUE;

    $this->renderer->renderRoot($element);
  }

  /**
   * Create an element with a child and subchild. Each element has the same
   * #lazy_builder callback, but with different contexts. They don't modify
   * markup, only attach additional drupalSettings.
   *
   * @covers ::render
   * @covers ::doRender
   * @covers \Drupal\Core\Render\RenderCache::get
   * @covers ::replacePlaceholders
   */
  public function testRenderChildrenPlaceholdersDifferentArguments() {
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
    $this->assertSame($expected_output, $output, 'Output is not overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertSame($expected_output, $output, '#markup is not overridden.');
    $expected_js_settings = [
      'foo' => 'bar',
      'dynamic_animal' => [$args_1[0] => TRUE, $args_2[0] => TRUE, $args_3[0] => TRUE],
    ];
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each placeholder #lazy_builder callback exist.');

    // GET request: validate cached data.
    $cached_element = $this->memoryCache->get('simpletest:drupal_render:children_placeholders')->data;
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

    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $parent = $xpath->query('//details/summary[text()="Parent"]')->length;
    $child =  $xpath->query('//details/div[@class="details-wrapper"]/details/summary[text()="Child"]')->length;
    $subchild = $xpath->query('//details/div[@class="details-wrapper"]/details/div[@class="details-wrapper" and text()="Subchild"]')->length;
    $this->assertTrue($parent && $child && $subchild, 'The correct data is cached: the stored #markup is not affected by placeholder #lazy_builder callbacks.');

    // Remove markup because it's compared above in the xpath.
    unset($cached_element['#markup']);
    $this->assertEquals($cached_element, $expected_element, 'The correct data is cached: the stored #attached properties are not affected by placeholder #lazy_builder callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($expected_output, $output, 'Output is not overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each placeholder #lazy_builder callback exist.');

    // Use the exact same element, but now unset #cache; ensure we get the same
    // result.
    unset($test_element['#cache']);
    $element = $test_element;
    $output = $this->renderer->renderRoot($element);
    $this->assertSame($expected_output, $output, 'Output is not overridden.');
    $this->assertSame($expected_output, $output, '#markup is not overridden.');
    $this->assertSame($element['#attached']['drupalSettings'], $expected_js_settings, '#attached is modified; both the original JavaScript setting and the ones added by each #lazy_builder callback exist.');
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
        'keys' => ['simpletest', 'drupal_render', 'children_placeholders'],
      ],
      '#title' => 'Parent',
      '#attached' => [
        'drupalSettings' => [
          'foo' => 'bar',
        ],
        'placeholders' => [
          'parent-x-parent' => [
            '#lazy_builder' => [ __NAMESPACE__ . '\\PlaceholdersTest::callback', $args_1],
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

/**
 * @see \Drupal\Tests\Core\Render\RendererPlaceholdersTest::testRecursivePlaceholder()
 */
class RecursivePlaceholdersTest {

  /**
   * #lazy_builder callback; bubbles another placeholder.
   *
   * @param string $animal
   *  An animal.
   *
   * @return array
   *   A renderable array.
   */
  public static function callback($animal) {
    return [
      'another' => [
        '#create_placeholder' => TRUE,
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', [$animal]],
      ],
    ];
  }

}
