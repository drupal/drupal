<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\RenderTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Performs functional tests on drupal_render().
 *
 * @group Common
 */
class RenderTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'common_test');

  /**
   * Tests the output drupal_render() for some elementary input values.
   */
  function testDrupalRenderBasics() {
    $types = array(
      array(
        'name' => 'null',
        'value' => NULL,
        'expected' => '',
      ),
      array(
        'name' => 'no value',
        'expected' => '',
      ),
      array(
        'name' => 'empty string',
        'value' => '',
        'expected' => '',
      ),
      array(
        'name' => 'no access',
        'value' => array(
          '#markup' => 'foo',
          '#access' => FALSE,
        ),
        'expected' => '',
      ),
      array(
        'name' => 'access denied via callback',
        'value' => array(
          '#markup' => 'foo',
          '#access_callback' => 'is_bool',
        ),
        'expected' => '',
      ),
      array(
        'name' => 'access granted via callback',
        'value' => array(
          '#markup' => 'foo',
          '#access_callback' => 'is_array',
        ),
        'expected' => 'foo',
      ),
      array(
        'name' => 'access FALSE is honored',
        'value' => array(
          '#markup' => 'foo',
          '#access' => FALSE,
          '#access_callback' => 'is_array',
        ),
        'expected' => '',
      ),
      array(
        'name' => 'previously printed',
        'value' => array(
          '#markup' => 'foo',
          '#printed' => TRUE,
        ),
        'expected' => '',
      ),
      array(
        'name' => 'printed in prerender',
        'value' => array(
          '#markup' => 'foo',
          '#pre_render' => array('common_test_drupal_render_printing_pre_render'),
        ),
        'expected' => '',
      ),

      // Test that #theme and #theme_wrappers can co-exist on an element.
      array(
        'name' => '#theme and #theme_wrappers basic',
        'value' => array(
          '#theme' => 'common_test_foo',
          '#foo' => 'foo',
          '#bar' => 'bar',
          '#theme_wrappers' => array('container'),
          '#attributes' => array('class' => array('baz')),
        ),
        'expected' => '<div class="baz">foobar</div>' . "\n",
      ),
      // Test that #theme_wrappers can disambiguate element attributes shared
      // with rendering methods that build #children by using the alternate
      // #theme_wrappers attribute override syntax.
      array(
        'name' => '#theme and #theme_wrappers attribute disambiguation',
        'value' => array(
          '#type' => 'link',
          '#theme_wrappers' => array(
            'container' => array(
              '#attributes' => array('class' => array('baz')),
            ),
          ),
          '#attributes' => array('id' => 'foo'),
          '#url' => Url::fromUri('http://drupal.org'),
          '#title' => 'bar',
        ),
        'expected' => '<div class="baz"><a href="http://drupal.org" id="foo">bar</a></div>' . "\n",
      ),
      // Test that #theme_wrappers can disambiguate element attributes when the
      // "base" attribute is not set for #theme.
      array(
        'name' => '#theme_wrappers attribute disambiguation with undefined #theme attribute',
        'value' => array(
          '#type' => 'link',
          '#url' => Url::fromUri('http://drupal.org'),
          '#title' => 'foo',
          '#theme_wrappers' => array(
            'container' => array(
              '#attributes' => array('class' => array('baz')),
            ),
          ),
        ),
        'expected' => '<div class="baz"><a href="http://drupal.org">foo</a></div>' . "\n",
      ),
      // Two 'container' #theme_wrappers, one using the "base" attributes and
      // one using an override.
      array(
        'name' => 'Two #theme_wrappers container hooks with different attributes',
        'value' => array(
          '#attributes' => array('class' => array('foo')),
          '#theme_wrappers' => array(
            'container' => array(
              '#attributes' => array('class' => array('bar')),
            ),
            'container',
          ),
        ),
        'expected' => '<div class="foo"><div class="bar"></div>' . "\n" . '</div>' . "\n",
      ),
      // Array syntax theme hook suggestion in #theme_wrappers.
      array(
        'name' => '#theme_wrappers implements an array style theme hook suggestion',
        'value' => array(
          '#theme_wrappers' => array(array('container')),
          '#attributes' => array('class' => array('foo')),
        ),
        'expected' => '<div class="foo"></div>' . "\n",
      ),

      // Test handling of #markup as a fallback for #theme hooks.
      // Simple #markup with no theme.
      array(
        'name' => 'basic #markup based renderable array',
        'value' => array('#markup' => 'foo'),
        'expected' => 'foo',
      ),
      // Theme suggestion is not implemented, #markup should be rendered.
      array(
        'name' => '#markup fallback for #theme suggestion not implemented',
        'value' => array(
          '#theme' => array('suggestionnotimplemented'),
          '#markup' => 'foo',
        ),
        'expected' => 'foo',
      ),
      // Theme suggestion is not implemented, child #markup should be rendered.
      array(
        'name' => '#markup fallback for child elements, #theme suggestion not implemented',
        'value' => array(
          '#theme' => array('suggestionnotimplemented'),
          'child' => array(
            '#markup' => 'foo',
          ),
        ),
        'expected' => 'foo',
      ),
      // Theme suggestion is implemented but returns empty string, #markup
      // should not be rendered.
      array(
        'name' => 'Avoid #markup if #theme is implemented but returns an empty string',
        'value' => array(
          '#theme' => array('common_test_empty'),
          '#markup' => 'foo',
        ),
        'expected' => '',
      ),
      // Theme suggestion is implemented but returns empty string, children
      // should not be rendered.
      array(
        'name' => 'Avoid rendering child elements if #theme is implemented but returns an empty string',
        'value' => array(
          '#theme' => array('common_test_empty'),
          'child' => array(
            '#markup' => 'foo',
          ),
        ),
        'expected' => '',
      ),

      // Test handling of #children and child renderable elements.
      // #theme is not set, #children is not set and the array has children.
      array(
        'name' => '#theme is not set, #children is not set and array has children',
        'value' => array(
          'child' => array('#markup' => 'bar'),
        ),
        'expected' => 'bar',
      ),
      // #theme is not set, #children is set but empty and the array has
      // children.
      array(
        'name' => '#theme is not set, #children is an empty string and array has children',
        'value' => array(
          '#children' => '',
          'child' => array('#markup' => 'bar'),
        ),
        'expected' => 'bar',
      ),
      // #theme is not set, #children is not empty and will be assumed to be the
      // rendered child elements even though the #markup for 'child' differs.
      array(
        'name' => '#theme is not set, #children is set and array has children',
        'value' => array(
          '#children' => 'foo',
          'child' => array('#markup' => 'bar'),
        ),
        'expected' => 'foo',
      ),
      // #theme is implemented so the values of both #children and 'child' will
      // be ignored - it is the responsibility of the theme hook to render these
      // if appropriate.
      array(
        'name' => '#theme is implemented, #children is set and array has children',
        'value' => array(
          '#theme' => 'common_test_foo',
          '#children' => 'baz',
          'child' => array('#markup' => 'boo'),
        ),
        'expected' => 'foobar',
      ),
      // #theme is implemented but #render_children is TRUE. As in the case
      // where #theme is not set, empty #children means child elements are
      // rendered recursively.
      array(
        'name' => '#theme is implemented, #render_children is TRUE, #children is empty and array has children',
        'value' => array(
          '#theme' => 'common_test_foo',
          '#children' => '',
          '#render_children' => TRUE,
          'child' => array(
            '#markup' => 'boo',
          ),
        ),
        'expected' => 'boo',
      ),
      // #theme is implemented but #render_children is TRUE. As in the case
      // where #theme is not set, #children will take precedence over 'child'.
      array(
        'name' => '#theme is implemented, #render_children is TRUE, #children is set and array has children',
        'value' => array(
          '#theme' => 'common_test_foo',
          '#children' => 'baz',
          '#render_children' => TRUE,
          'child' => array(
            '#markup' => 'boo',
          ),
        ),
        'expected' => 'baz',
      ),
    );

    foreach($types as $type) {
      $this->assertIdentical(drupal_render($type['value']), $type['expected'], '"' . $type['name'] . '" input rendered correctly by drupal_render().');
    }
  }

  /**
   * Tests sorting by weight.
   */
  function testDrupalRenderSorting() {
    $first = $this->randomMachineName();
    $second = $this->randomMachineName();
    // Build an array with '#weight' set for each element.
    $elements = array(
      'second' => array(
        '#weight' => 10,
        '#markup' => $second,
      ),
      'first' => array(
        '#weight' => 0,
        '#markup' => $first,
      ),
    );
    $output = drupal_render($elements);

    // The lowest weight element should appear last in $output.
    $this->assertTrue(strpos($output, $second) > strpos($output, $first), 'Elements were sorted correctly by weight.');

    // Confirm that the $elements array has '#sorted' set to TRUE.
    $this->assertTrue($elements['#sorted'], "'#sorted' => TRUE was added to the array");

    // Pass $elements through \Drupal\Core\Render\Element::children() and
    // ensure it remains sorted in the correct order. drupal_render() will
    // return an empty string if used on the same array in the same request.
    $children = Element::children($elements);
    $this->assertTrue(array_shift($children) == 'first', 'Child found in the correct order.');
    $this->assertTrue(array_shift($children) == 'second', 'Child found in the correct order.');


    // The same array structure again, but with #sorted set to TRUE.
    $elements = array(
      'second' => array(
        '#weight' => 10,
        '#markup' => $second,
      ),
      'first' => array(
        '#weight' => 0,
        '#markup' => $first,
      ),
      '#sorted' => TRUE,
    );
    $output = drupal_render($elements);

    // The elements should appear in output in the same order as the array.
    $this->assertTrue(strpos($output, $second) < strpos($output, $first), 'Elements were not sorted.');
  }

  /**
   * Tests #attached functionality in children elements.
   */
  function testDrupalRenderChildrenAttached() {
    // The cache system is turned off for POST requests.
    $request_method = \Drupal::request()->getMethod();
    \Drupal::request()->setMethod('GET');

    // Create an element with a child and subchild. Each element loads a
    // different JavaScript file using #attached.
    $parent_js = drupal_get_path('module', 'user') . '/user.js';
    $child_js = drupal_get_path('module', 'forum') . '/forum.js';
    $subchild_js = drupal_get_path('module', 'book') . '/book.js';
    $element = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#cache' => array(
        'keys' => array('simpletest', 'drupal_render', 'children_attached'),
      ),
      '#attached' => array('js' => array($parent_js)),
      '#title' => 'Parent',
    );
    $element['child'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#attached' => array('js' => array($child_js)),
      '#title' => 'Child',
    );
    $element['child']['subchild'] = array(
      '#attached' => array('js' => array($subchild_js)),
      '#markup' => 'Subchild',
    );

    // Render the element and verify the presence of #attached JavaScript.
    drupal_render($element);
    $expected_js = [$parent_js, $child_js, $subchild_js];
    $this->assertEqual($element['#attached']['js'], $expected_js, 'The element, child and subchild #attached JavaScript are included.');

    // Load the element from cache and verify the presence of the #attached
    // JavaScript.
    $element = array('#cache' => array('keys' => array('simpletest', 'drupal_render', 'children_attached')));
    $this->assertTrue(strlen(drupal_render($element)) > 0, 'The element was retrieved from cache.');
    $this->assertEqual($element['#attached']['js'], $expected_js, 'The element, child and subchild #attached JavaScript are included.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }

  /**
   * Tests passing arguments to the theme function.
   */
  function testDrupalRenderThemeArguments() {
    $element = array(
      '#theme' => 'common_test_foo',
    );
    // Test that defaults work.
    $this->assertEqual(drupal_render($element), 'foobar', 'Defaults work');
    $element = array(
      '#theme' => 'common_test_foo',
      '#foo' => $this->randomMachineName(),
      '#bar' => $this->randomMachineName(),
    );
    // Tests that passing arguments to the theme function works.
    $this->assertEqual(drupal_render($element), $element['#foo'] . $element['#bar'], 'Passing arguments to theme functions works');
  }

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  function testDrupalRenderThemePreprocessAttached() {
    \Drupal::state()->set('theme_preprocess_attached_test', TRUE);

    $test_element = [
      '#theme' => 'common_test_render_element',
      'foo' => [
        '#markup' => 'Kittens!',
      ],
    ];
    drupal_render($test_element);

    $expected_attached = [
      'library' => [
        'test/generic_preprocess',
        'test/specific_preprocess',
      ]
    ];
    $this->assertEqual($expected_attached, $test_element['#attached'], 'All expected assets from theme preprocess hooks attached.');

    \Drupal::state()->set('theme_preprocess_attached_test', FALSE);
  }

  /**
   * Tests caching of an empty render item.
   */
  function testDrupalRenderCache() {
    // The cache system is turned off for POST requests.
    $request_method = \Drupal::request()->getMethod();
    \Drupal::request()->setMethod('GET');

    // Create an empty element.
    $test_element = array(
      '#cache' => array(
        'cid' => 'render_cache_test',
        'tags' => array('render_cache_tag'),
      ),
      '#markup' => '',
      'child' => array(
        '#cache' => array(
          'cid' => 'render_cache_test_child',
          'tags' => array('render_cache_tag_child:1', 'render_cache_tag_child:2'),
        ),
        '#markup' => '',
      ),
    );

    // Render the element and confirm that it goes through the rendering
    // process (which will set $element['#printed']).
    $element = $test_element;
    drupal_render($element);
    $this->assertTrue(isset($element['#printed']), 'No cache hit');

    // Render the element again and confirm that it is retrieved from the cache
    // instead (so $element['#printed'] will not be set).
    $element = $test_element;
    drupal_render($element);
    $this->assertFalse(isset($element['#printed']), 'Cache hit');

    // Test that cache tags are correctly collected from the render element,
    // including the ones from its subchild.
    $expected_tags = array(
      'render_cache_tag',
      'render_cache_tag_child:1',
      'render_cache_tag_child:2',
      'rendered',
    );
    $this->assertEqual($expected_tags, $element['#cache']['tags'], 'Cache tags were collected from the element and its subchild.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }

  /**
   * Tests post-render cache callbacks functionality.
   */
  function testDrupalRenderPostRenderCache() {
    $context = array('foo' => $this->randomContextValue());
    $test_element = array();
    $test_element['#markup'] = '';
    $test_element['#attached']['js'][] = array('type' => 'setting', 'data' => array('foo' => 'bar'));
    $test_element['#post_render_cache']['common_test_post_render_cache'] = array(
      $context
    );

    // #cache disabled.
    $element = $test_element;
    $element['#markup'] = '<p>#cache disabled</p>';
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js = [
      ['type' => 'setting', 'data' => ['foo' => 'bar']],
      ['type' => 'setting', 'data' => ['common_test' => $context]],
    ];
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the one added by the #post_render_cache callback exist.');

    // The cache system is turned off for POST requests.
    $request_method = \Drupal::request()->getMethod();
    \Drupal::request()->setMethod('GET');

    // GET request: #cache enabled, cache miss.
    $element = $test_element;
    $element['#cache'] = array('cid' => 'post_render_cache_test_GET');
    $element['#markup'] = '<p>#cache enabled, GET</p>';
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js = [
      ['type' => 'setting', 'data' => ['foo' => 'bar']],
      ['type' => 'setting', 'data' => ['common_test' => $context]],
    ];
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the one added by the #post_render_cache callback exist.');

    // GET request: validate cached data.
    $element = array('#cache' => array('cid' => 'post_render_cache_test_GET'));
    $cached_element = \Drupal::cache('render')->get(drupal_render_cid_create($element))->data;
    $expected_element = array(
      '#markup' => '<p>#cache enabled, GET</p>',
      '#attached' => $test_element['#attached'],
      '#post_render_cache' => $test_element['#post_render_cache'],
      '#cache' => array('tags' => array('rendered')),
    );
    $this->assertIdentical($cached_element, $expected_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    $element['#cache'] = array('cid' => 'post_render_cache_test_GET');
    $element['#markup'] = '<p>#cache enabled, GET</p>';
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js = [
      ['type' => 'setting', 'data' => ['foo' => 'bar']],
      ['type' => 'setting', 'data' => ['common_test' => $context]],
    ];
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the one added by the #post_render_cache callback exist.');

    // Verify behavior when handling a non-GET request, e.g. a POST request:
    // also in that case, #post_render_cache callbacks must be called.
    \Drupal::request()->setMethod('POST');

    // POST request: #cache enabled, cache miss.
    $element = $test_element;
    $element['#cache'] = array('cid' => 'post_render_cache_test_POST');
    $element['#markup'] = '<p>#cache enabled, POST</p>';
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js = [
      ['type' => 'setting', 'data' => ['foo' => 'bar']],
      ['type' => 'setting', 'data' => ['common_test' => $context]],
    ];
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the one added by the #post_render_cache callback exist.');

    // POST request: Ensure no data was cached.
    $element = array('#cache' => array('cid' => 'post_render_cache_test_POST'));
    $cached_element = \Drupal::cache('render')->get(drupal_render_cid_create($element));
    $this->assertFalse($cached_element, 'No data is cached because this is a POST request.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }

  /**
   * Tests post-render cache callbacks functionality in children elements.
   */
  function testDrupalRenderChildrenPostRenderCache() {
    // The cache system is turned off for POST requests.
    $request_method = \Drupal::request()->getMethod();
    \Drupal::request()->setMethod('GET');

    // Test case 1.
    // Create an element with a child and subchild. Each element has the same
    // #post_render_cache callback, but with different contexts.
    $context_1 = array('foo' => $this->randomContextValue());
    $context_2 = array('bar' => $this->randomContextValue());
    $context_3 = array('baz' => $this->randomContextValue());
    $test_element = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#cache' => array(
        'keys' => array('simpletest', 'drupal_render', 'children_post_render_cache'),
      ),
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array($context_1)
      ),
      '#title' => 'Parent',
      '#attached' => array(
        'js' => array(
          array('type' => 'setting', 'data' => array('foo' => 'bar'))
        ),
      ),
    );
    $test_element['child'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array($context_2)
      ),
      '#title' => 'Child',
    );
    $test_element['child']['subchild'] = array(
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array($context_3)
      ),
      '#markup' => 'Subchild',
    );
    $element = $test_element;
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $expected_js = [
      ['type' => 'setting', 'data' => ['foo' => 'bar']],
      ['type' => 'setting', 'data' => ['common_test' => $context_1 ]],
      ['type' => 'setting', 'data' => ['common_test' => $context_2 ]],
      ['type' => 'setting', 'data' => ['common_test' => $context_3 ]],
    ];
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // GET request: validate cached data.
    $element = array('#cache' => $element['#cache']);
    $cached_element = \Drupal::cache('render')->get(drupal_render_cid_create($element))->data;
    $expected_element = array(
      '#attached' => array(
        'js' => array(
          array('type' => 'setting', 'data' => array('foo' => 'bar'))
        ),
        'library' => array(
          'core/drupal.collapse',
          'core/drupal.collapse',
        ),
      ),
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array(
          $context_1,
          $context_2,
          $context_3,
        )
      ),
      '#cache' => array('tags' => array('rendered')),
    );

    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $parent = $xpath->query('//details[@class="form-wrapper" and @open="open"]/summary[@role="button" and @aria-expanded and text()="Parent"]')->length;
    $child =  $xpath->query('//details[@class="form-wrapper" and @open="open"]/div[@class="details-wrapper"]/details[@class="form-wrapper" and @open="open"]/summary[@role="button" and @aria-expanded and text()="Child"]')->length;
    $subchild = $xpath->query('//details[@class="form-wrapper" and @open="open"]/div[@class="details-wrapper"]/details[@class="form-wrapper" and @open="open"]/div [@class="details-wrapper" and text()="Subchild"]')->length;
    $this->assertTrue($parent && $child && $subchild, 'The correct data is cached: the stored #markup is not affected by #post_render_cache callbacks.');

    // Remove markup because it's compared above in the xpath.
    unset($cached_element['#markup']);
    $this->assertIdentical($cached_element, $expected_element, 'The correct data is cached: the stored #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // Test case 2.
    // Use the exact same element, but now unset #cache.
    unset($test_element['#cache']);
    $element = $test_element;
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // Test case 3.
    // Create an element with a child and subchild. Each element has the same
    // #post_render_cache callback, but with different contexts. Both the
    // parent and the child elements have #cache set. The cached parent element
    // must contain the pristine child element, i.e. unaffected by its
    // #post_render_cache callbacks. I.e. the #post_render_cache callbacks may
    // not yet have run, or otherwise the cached parent element would contain
    // personalized data, thereby breaking the render cache.
    $element = $test_element;
    $element['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_parent');
    $element['child']['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_child');
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // GET request: validate cached data for both the parent and child.
    $element = $test_element;
    $element['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_parent');
    $element['child']['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_child');
    $cached_parent_element = \Drupal::cache('render')->get(drupal_render_cid_create($element))->data;
    $cached_child_element = \Drupal::cache('render')->get(drupal_render_cid_create($element['child']))->data;
    $expected_parent_element = array(
      '#attached' => array(
        'js' => array(
          array('type' => 'setting', 'data' => array('foo' => 'bar'))
        ),
        'library' => array(
          'core/drupal.collapse',
          'core/drupal.collapse',
        ),
      ),
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array(
          $context_1,
          $context_2,
          $context_3,
        )
      ),
      '#cache' => array('tags' => array('rendered')),
    );

    $dom = Html::load($cached_parent_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $parent = $xpath->query('//details[@class="form-wrapper" and @open="open"]/summary[@role="button" and @aria-expanded and text()="Parent"]')->length;
    $child =  $xpath->query('//details[@class="form-wrapper" and @open="open"]/div[@class="details-wrapper"]/details[@class="form-wrapper" and @open="open"]/summary[@role="button" and @aria-expanded and text()="Child"]')->length;
    $subchild = $xpath->query('//details[@class="form-wrapper" and @open="open"]/div[@class="details-wrapper"]/details[@class="form-wrapper" and @open="open"]/div [@class="details-wrapper" and text()="Subchild"]')->length;
    $this->assertTrue($parent && $child && $subchild, 'The correct data is cached for the parent: the stored #markup is not affected by #post_render_cache callbacks.');

    // Remove markup because it's compared above in the xpath.
    unset($cached_parent_element['#markup']);
    $this->assertIdentical($cached_parent_element, $expected_parent_element, 'The correct data is cached for the parent: the stored #attached properties are not affected by #post_render_cache callbacks.');

    $expected_child_element = array(
      '#attached' => array(
        'library' => array(
          'core/drupal.collapse',
        ),
      ),
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array(
          $context_2,
          $context_3,
        )
      ),
      '#cache' => array('tags' => array('rendered')),
    );

    $dom = Html::load($cached_child_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $child =  $xpath->query('//details[@class="form-wrapper" and @open="open"]/summary[@role="button" and @aria-expanded and text()="Child"]')->length;
    $subchild = $xpath->query('//details[@class="form-wrapper" and @open="open"]/div [@class="details-wrapper" and text()="Subchild"]')->length;
    $this->assertTrue($child && $subchild, 'The correct data is cached for the child: the stored #markup is not affected by #post_render_cache callbacks.');

    // Remove markup because it's compared above in the xpath.
    unset($cached_child_element['#markup']);
    $this->assertIdentical($cached_child_element, $expected_child_element, 'The correct data is cached for the child: the stored #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit, parent element.
    $element = $test_element;
    $element['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_parent');
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // GET request: #cache enabled, cache hit, child element.
    $element = $test_element;
    $element['child']['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_child');
    $element = $element['child'];
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $expected_js = [
      ['type' => 'setting', 'data' => ['common_test' => $context_2 ]],
      ['type' => 'setting', 'data' => ['common_test' => $context_3 ]],
    ];
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; both the original JavaScript setting and the ones added by each #post_render_cache callback exist.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }

  /**
   * Tests post-render cache-integrated 'render_cache_placeholder' element.
   */
  function testDrupalRenderRenderCachePlaceholder() {
    $context = array(
      'bar' => $this->randomContextValue(),
    );
    $callback = 'common_test_post_render_cache_placeholder';
    $placeholder = drupal_render_cache_generate_placeholder($callback, $context);
    $this->assertIdentical($placeholder, Html::normalize($placeholder), 'Placeholder unaltered by Html::normalize() which is used by FilterHtmlCorrector.');

    $test_element = array(
      '#post_render_cache' => array(
        $callback => array(
          $context
        ),
      ),
      '#markup' => $placeholder,
      '#prefix' => '<foo>',
      '#suffix' => '</foo>'
    );
    $expected_output = '<foo><bar>' . $context['bar'] . '</bar></foo>';

    // #cache disabled.
    $element = $test_element;
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $expected_js = [
      ['type' => 'setting', 'data' => ['common_test' => $context]],
    ];
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; JavaScript setting is added to page.');

    // The cache system is turned off for POST requests.
    $request_method = \Drupal::request()->getMethod();
    \Drupal::request()->setMethod('GET');

    // GET request: #cache enabled, cache miss.
    $element = $test_element;
    $element['#cache'] = array('cid' => 'render_cache_placeholder_test_GET');
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; JavaScript setting is added to page.');

    // GET request: validate cached data.
    $expected_token = $element['#post_render_cache']['common_test_post_render_cache_placeholder'][0]['token'];
    $element = array('#cache' => array('cid' => 'render_cache_placeholder_test_GET'));
    $cached_element = \Drupal::cache('render')->get(drupal_render_cid_create($element))->data;
    // Parse unique token out of the cached markup.
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $this->assertTrue($nodes->length, 'The token attribute was found in the cached markup');
    $token = '';
    if ($nodes->length) {
      $token = $nodes->item(0)->getAttribute('token');
    }
    $this->assertIdentical($token, $expected_token, 'The tokens are identical');
    // Verify the token is in the cached element.
    $expected_element = array(
      '#markup' => '<foo><drupal-render-cache-placeholder callback="common_test_post_render_cache_placeholder" token="'. $expected_token . '"></drupal-render-cache-placeholder></foo>',
      '#attached' => array(),
      '#post_render_cache' => array(
        'common_test_post_render_cache_placeholder' => array(
          $context
        ),
      ),
      '#cache' => array('tags' => array('rendered')),
    );
    $this->assertIdentical($cached_element, $expected_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $element['#cache'] = array('cid' => 'render_cache_placeholder_test_GET');
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertIdentical($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; JavaScript setting is added to page.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }

  /**
   * Tests child element that uses #post_render_cache but that is rendered via a
   * template.
   */
  function testDrupalRenderChildElementRenderCachePlaceholder() {
    $context = array(
      'bar' => $this->randomContextValue(),
    );
    $callback = 'common_test_post_render_cache_placeholder';
    $placeholder = drupal_render_cache_generate_placeholder($callback, $context);
    $test_element = [
      '#theme' => 'common_test_render_element',
      'foo' => [
        '#post_render_cache' => [
          $callback => [
            $context
          ],
        ],
        '#markup' => $placeholder,
        '#prefix' => '<foo>',
        '#suffix' => '</foo>'
      ],
    ];
    $expected_output = '<foo><bar>' . $context['bar'] . '</bar></foo>' . "\n";

    // #cache disabled.
    $element = $test_element;
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $expected_js = [
      ['type' => 'setting', 'data' => ['common_test' => $context]],
    ];
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; JavaScript setting is added to page.');

    // The cache system is turned off for POST requests.
    $request_method = \Drupal::request()->getMethod();
    \Drupal::request()->setMethod('GET');

    // GET request: #cache enabled, cache miss.
    $element = $test_element;
    $element['#cache'] = array('cid' => 'render_cache_placeholder_test_GET');
    $element['foo']['#cache'] = array('cid' => 'render_cache_placeholder_test_child_GET');
    // Render, which will use the common-test-render-element.html.twig template.
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output); //, 'Placeholder was replaced in output');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; JavaScript setting is added to page.');

    // GET request: validate cached data for child element.
    $child_tokens = $element['foo']['#post_render_cache']['common_test_post_render_cache_placeholder'][0]['token'];
    $parent_tokens = $element['#post_render_cache']['common_test_post_render_cache_placeholder'][0]['token'];
    $expected_token = $child_tokens;
    $element = array('#cache' => array('cid' => 'render_cache_placeholder_test_child_GET'));
    $cached_element = \Drupal::cache('render')->get(drupal_render_cid_create($element))->data;
    // Parse unique token out of the cached markup.
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $this->assertTrue($nodes->length, 'The token attribute was found in the cached child element markup');
    $token = '';
    if ($nodes->length) {
      $token = $nodes->item(0)->getAttribute('token');
    }
    $this->assertIdentical($token, $expected_token, 'The tokens are identical for the child element');
    // Verify the token is in the cached element.
    $expected_element = array(
      '#markup' => '<foo><drupal-render-cache-placeholder callback="common_test_post_render_cache_placeholder" token="'. $expected_token . '"></drupal-render-cache-placeholder></foo>',
      '#attached' => array(),
      '#post_render_cache' => array(
        'common_test_post_render_cache_placeholder' => array(
          $context,
        ),
      ),
      '#cache' => array('tags' => array('rendered')),
    );
    $this->assertIdentical($cached_element, $expected_element, 'The correct data is cached for the child element: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: validate cached data (for the parent/entire render array).
    $element = array('#cache' => array('cid' => 'render_cache_placeholder_test_GET'));
    $cached_element = \Drupal::cache('render')->get(drupal_render_cid_create($element))->data;
    // Parse unique token out of the cached markup.
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $this->assertTrue($nodes->length, 'The token attribute was found in the cached parent element markup');
    $token = '';
    if ($nodes->length) {
      $token = $nodes->item(0)->getAttribute('token');
    }
    $this->assertIdentical($token, $expected_token, 'The tokens are identical for the parent element');
    // Verify the token is in the cached element.
    $expected_element = array(
      '#markup' => '<foo><drupal-render-cache-placeholder callback="common_test_post_render_cache_placeholder" token="'. $expected_token . '"></drupal-render-cache-placeholder></foo>' . "\n",
      '#attached' => array(),
      '#post_render_cache' => array(
        'common_test_post_render_cache_placeholder' => array(
          $context,
        ),
      ),
      '#cache' => array('tags' => array('rendered')),
    );
    $this->assertIdentical($cached_element, $expected_element); //, 'The correct data is cached for the parent element: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: validate cached data.
    // Check the cache of the child element again after the parent has been
    // rendered.
    $element = array('#cache' => array('cid' => 'render_cache_placeholder_test_child_GET'));
    $cached_element = \Drupal::cache('render')->get(drupal_render_cid_create($element))->data;
    // Verify that the child element contains the correct
    // render_cache_placeholder markup.
    $expected_token = $child_tokens;
    $dom = Html::load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $this->assertTrue($nodes->length, 'The token attribute was found in the cached child element markup');
    $token = '';
    if ($nodes->length) {
      $token = $nodes->item(0)->getAttribute('token');
    }
    $this->assertIdentical($token, $expected_token, 'The tokens are identical for the child element');
    // Verify the token is in the cached element.
    $expected_element = array(
      '#markup' => '<foo><drupal-render-cache-placeholder callback="common_test_post_render_cache_placeholder" token="'. $expected_token . '"></drupal-render-cache-placeholder></foo>',
      '#attached' => array(),
      '#post_render_cache' => array(
        'common_test_post_render_cache_placeholder' => array(
          $context,
        ),
      ),
      '#cache' => array('tags' => array('rendered')),
    );
    $this->assertIdentical($cached_element, $expected_element, 'The correct data is cached for the child element: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    $element = $test_element;
    $element['#cache'] = array('cid' => 'render_cache_placeholder_test_GET');
    // Render, which will use the common-test-render-element.html.twig template.
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertIdentical($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $this->assertIdentical($element['#attached']['js'], $expected_js, '#attached is modified; JavaScript setting is added to page.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }

  /**
   * #pre_render callback for testDrupalRenderBubbling().
   */
  public static function bubblingPreRender($elements) {
    $callback = 'Drupal\system\Tests\Common\RenderTest::bubblingPostRenderCache';
    $context = array(
      'foo' => 'bar',
      'baz' => 'qux',
    );
    $placeholder = drupal_render_cache_generate_placeholder($callback, $context);
    $elements += array(
      'child_cache_tag' => array(
        '#cache' => array(
          'tags' => array('child:cache_tag'),
        ),
        '#markup' => 'Cache tag!',
      ),
      'child_asset' => array(
        '#attached' => array(
          'js' => array(
            array(
              'type' => 'setting',
              'data' => array('foo' => 'bar'),
            )
          ),
        ),
        '#markup' => 'Asset!',
      ),
      'child_post_render_cache' => array(
        '#post_render_cache' => array(
          $callback => array(
            $context,
          ),
        ),
        '#markup' => $placeholder,
      ),
      'child_nested_pre_render_uncached' => array(
        '#cache' => array('cid' => 'uncached_nested'),
        '#pre_render' => array('Drupal\system\Tests\Common\RenderTest::bubblingNestedPreRenderUncached'),
      ),
      'child_nested_pre_render_cached' => array(
        '#cache' => array('cid' => 'cached_nested'),
        '#pre_render' => array('Drupal\system\Tests\Common\RenderTest::bubblingNestedPreRenderCached'),
      ),
    );
    return $elements;
  }

  /**
   * #pre_render callback for testDrupalRenderBubbling().
   */
  public static function bubblingNestedPreRenderUncached($elements) {
    \Drupal::state()->set('bubbling_nested_pre_render_uncached', TRUE);
    $elements['#markup'] = 'Nested!';
    return $elements;
  }

  /**
   * #pre_render callback for testDrupalRenderBubbling().
   */
  public static function bubblingNestedPreRenderCached($elements) {
    \Drupal::state()->set('bubbling_nested_pre_render_cached', TRUE);
    return $elements;
  }

  /**
   * #post_render_cache callback for testDrupalRenderBubbling().
   */
  public static function bubblingPostRenderCache(array $element, array $context) {
    $callback = 'Drupal\system\Tests\Common\RenderTest::bubblingPostRenderCache';
    $placeholder = drupal_render_cache_generate_placeholder($callback, $context);
    $element['#markup'] = str_replace($placeholder, 'Post-render cache!' . $context['foo'] . $context['baz'], $element['#markup']);
    return $element;
  }

  /**
   * Tests bubbling of assets, cache tags and post-render cache callbacks when
   * they are added by #pre_render callbacks.
   */
  function testDrupalRenderBubbling() {
    $verify_result= function ($test_element) {
      \Drupal::state()->set('bubbling_nested_pre_render_uncached', FALSE);
      \Drupal::state()->set('bubbling_nested_pre_render_cached', FALSE);
      \Drupal::cache('render')->set('cached_nested', array('#markup' => 'Cached nested!', '#attached' => array(), '#cache' => array('tags' => array()), '#post_render_cache' => array()));
      \Drupal::cache('render')->delete('uncached_nested');

      $output = drupal_render($test_element);
      // Assert top-level.
      $this->assertEqual('Cache tag!Asset!Post-render cache!barquxNested!Cached nested!', trim($output), 'Expected HTML generated.');
      $this->assertEqual(array('child:cache_tag'), $test_element['#cache']['tags'], 'Expected cache tags found.');
      $expected_attached = array(
        'js' => array(
          0 => array(
            'type' => 'setting',
            'data' => array('foo' => 'bar'),
          ),
        ),
      );
      $this->assertEqual($expected_attached, $test_element['#attached'], 'Expected assets found.');
      $expected_post_render_cache = array(
        'Drupal\\system\\Tests\\Common\\RenderTest::bubblingPostRenderCache' => array(
          0 => array (
            'foo' => 'bar',
            'baz' => 'qux',
          ),
        ),
      );
      $post_render_cache = $test_element['#post_render_cache'];
      // We don't care about the exact token.
      unset($post_render_cache['Drupal\\system\\Tests\\Common\\RenderTest::bubblingPostRenderCache'][0]['token']);
      $this->assertEqual($expected_post_render_cache, $post_render_cache, 'Expected post-render cache data found.');

      // Ensure that #pre_render callbacks are only executed if they don't have
      // a render cache hit.
      $this->assertTrue(\Drupal::state()->get('bubbling_nested_pre_render_uncached'));
      $this->assertFalse(\Drupal::state()->get('bubbling_nested_pre_render_cached'));
    };

    $this->pass('Test <strong>without</strong> theming/Twig.');
    $test_element_without_theme = array(
      'foo' => array(
        '#pre_render' => array(array(get_class($this), 'bubblingPreRender')),
      ),
    );
    $verify_result($test_element_without_theme);

    $this->pass('Test <strong>with</strong> theming/Twig.');
    $test_element_with_theme = array(
      '#theme' => 'common_test_render_element',
      'foo' => array(
        '#pre_render' => array(array(get_class($this), 'bubblingPreRender')),
      ),
    );
    $verify_result($test_element_with_theme);
  }

  /**
   * Generates a random context value for the post-render cache tests.
   *
   * The #context array used by the post-render cache callback will generally
   * be used to provide metadata like entity IDs, field machine names, paths,
   * etc. for JavaScript replacement of content or assets. In this test, the
   * callbacks common_test_post_render_cache() and
   * common_test_post_render_cache_placeholder() render the context inside test
   * HTML, so using any random string would sometimes cause random test
   * failures because the test output would be unparseable. Instead, we provide
   * random tokens for replacement.
   *
   * @see common_test_post_render_cache()
   * @see common_test_post_render_cache_placeholder()
   * @see https://drupal.org/node/2151609
   */
  protected function randomContextValue() {
    $tokens = array('llama', 'alpaca', 'camel', 'moose', 'elk');
    return $tokens[mt_rand(0, 4)];
  }

  /**
   * Tests drupal_process_attached().
   */
  public function testDrupalProcessAttached() {
    // Specify invalid attachments in a render array.
    $build['#attached']['library'][] = 'core/drupal.states';
    $build['#attached']['drupal_process_states'][] = [];
    try {
      drupal_process_attached($build);
      $this->fail("Invalid #attachment 'drupal_process_states' allowed");
    }
    catch (\Exception $e) {
      $this->pass("Invalid #attachment 'drupal_process_states' not allowed");
    }
  }

}
