<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\RendererBubblingTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Render\Element;
use Drupal\Core\State\State;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 */
class RendererBubblingTest extends RendererTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpRequest();
    $this->setupMemoryCache();
  }

  /**
   * Tests bubbling of assets when NOT using #pre_render callbacks.
   */
  public function testBubblingWithoutPreRender() {
    $this->elementInfo->expects($this->any())
      ->method('getInfo')
      ->willReturn([]);

    // Create an element with a child and subchild. Each element loads a
    // different library using #attached.
    $element = [
      '#type' => 'container',
      '#cache' => [
        'keys' => ['simpletest', 'drupal_render', 'children_attached'],
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
    $this->renderer->render($element);
    $expected_libraries = ['test/parent', 'test/child', 'test/subchild'];
    $this->assertEquals($element['#attached']['library'], $expected_libraries, 'The element, child and subchild #attached libraries are included.');

    // Load the element from cache and verify the presence of the #attached
    // JavaScript.
    $element = ['#cache' => ['keys' => ['simpletest', 'drupal_render', 'children_attached']]];
    $this->assertTrue(strlen($this->renderer->render($element)) > 0, 'The element was retrieved from cache.');
    $this->assertEquals($element['#attached']['library'], $expected_libraries, 'The element, child and subchild #attached libraries are included.');
  }

  /**
   * Tests bubbling of bubbleable metadata added by #pre_render callbacks.
   *
   * @dataProvider providerTestBubblingWithPrerender
   */
  public function testBubblingWithPrerender($test_element) {
    // Mock the State service.
    $memory_state = new State(new KeyValueMemoryFactory());;
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
    $this->memoryCache->set('cached_nested', ['#markup' => 'Cached nested!', '#attached' => [], '#cache' => ['tags' => []], '#post_render_cache' => []]);

    // Simulate the rendering of an entire response (i.e. a root call).
    $output = $this->renderer->renderRoot($test_element);

    // First, assert the render array is of the expected form.
    $this->assertEquals('Cache tag!Asset!Post-render cache!barquxNested!Cached nested!', trim($output), 'Expected HTML generated.');
    $this->assertEquals(['child:cache_tag'], $test_element['#cache']['tags'], 'Expected cache tags found.');
    $expected_attached = [
      'drupalSettings' => ['foo' => 'bar'],
    ];
    $this->assertEquals($expected_attached, $test_element['#attached'], 'Expected assets found.');
    $this->assertEquals([], $test_element['#post_render_cache'], '#post_render_cache property is empty after rendering');

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
    $data[] = [[
      'foo' => [
        '#pre_render' => [__NAMESPACE__ . '\\BubblingTest::bubblingPreRender'],
      ]]];

    // Test element with theme.
    $data[] = [[
      '#theme' => 'common_test_render_element',
      'foo' => [
        '#pre_render' => [__NAMESPACE__ . '\\BubblingTest::bubblingPreRender'],
      ]]];

    return $data;
  }

}


class BubblingTest {

  /**
   * #pre_render callback for testBubblingWithPrerender().
   */
  public static function bubblingPreRender($elements) {
    $callback = __CLASS__ . '::bubblingPostRenderCache';
    $context = [
      'foo' => 'bar',
      'baz' => 'qux',
    ];
    $placeholder = \Drupal::service('renderer')->generateCachePlaceholder($callback, $context);
    $elements += [
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
      'child_post_render_cache' => [
        '#post_render_cache' => [
          $callback => [
            $context,
          ],
        ],
        '#markup' => $placeholder,
      ],
      'child_nested_pre_render_uncached' => [
        '#cache' => ['cid' => 'uncached_nested'],
        '#pre_render' => [__CLASS__ . '::bubblingNestedPreRenderUncached'],
      ],
      'child_nested_pre_render_cached' => [
        '#cache' => ['cid' => 'cached_nested'],
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
   * #post_render_cache callback for testBubblingWithPrerender().
   */
  public static function bubblingPostRenderCache(array $element, array $context) {
    $callback = __CLASS__ . '::bubblingPostRenderCache';
    $placeholder = \Drupal::service('renderer')->generateCachePlaceholder($callback, $context);
    $element['#markup'] = str_replace($placeholder, 'Post-render cache!' . $context['foo'] . $context['baz'], $element['#markup']);
    return $element;
  }

}
