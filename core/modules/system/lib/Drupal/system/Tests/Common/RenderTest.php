<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\RenderTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests drupal_render().
 */
class RenderTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'common_test');

  public static function getInfo() {
    return array(
      'name' => 'drupal_render()',
      'description' => 'Performs functional tests on drupal_render().',
      'group' => 'Common',
    );
  }

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
          '#attributes' => array('class' => 'baz'),
        ),
        'expected' => '<div class="baz">foobar</div>',
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
              '#attributes' => array('class' => 'baz'),
            ),
          ),
          '#attributes' => array('id' => 'foo'),
          '#href' => 'http://drupal.org',
          '#title' => 'bar',
        ),
        'expected' => '<div class="baz"><a href="http://drupal.org" id="foo">bar</a></div>',
      ),
      // Test that #theme_wrappers can disambiguate element attributes when the
      // "base" attribute is not set for #theme.
      array(
        'name' => '#theme_wrappers attribute disambiguation with undefined #theme attribute',
        'value' => array(
          '#type' => 'link',
          '#href' => 'http://drupal.org',
          '#title' => 'foo',
          '#theme_wrappers' => array(
            'container' => array(
              '#attributes' => array('class' => 'baz'),
            ),
          ),
        ),
        'expected' => '<div class="baz"><a href="http://drupal.org">foo</a></div>',
      ),
      // Two 'container' #theme_wrappers, one using the "base" attributes and
      // one using an override.
      array(
        'name' => 'Two #theme_wrappers container hooks with different attributes',
        'value' => array(
          '#attributes' => array('class' => 'foo'),
          '#theme_wrappers' => array(
            'container' => array(
              '#attributes' => array('class' => 'bar'),
            ),
            'container',
          ),
        ),
        'expected' => '<div class="foo"><div class="bar"></div></div>',
      ),
      // Array syntax theme hook suggestion in #theme_wrappers.
      array(
        'name' => '#theme_wrappers implements an array style theme hook suggestion',
        'value' => array(
          '#theme_wrappers' => array(array('container')),
          '#attributes' => array('class' => 'foo'),
        ),
        'expected' => '<div class="foo"></div>',
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
    $first = $this->randomName();
    $second = $this->randomName();
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

    // Pass $elements through element_children() and ensure it remains
    // sorted in the correct order. drupal_render() will return an empty string
    // if used on the same array in the same request.
    $children = element_children($elements);
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
      '#cache' => array(
        'keys' => array('simpletest', 'drupal_render', 'children_attached'),
      ),
      '#attached' => array('js' => array($parent_js)),
      '#title' => 'Parent',
    );
    $element['child'] = array(
      '#type' => 'details',
      '#attached' => array('js' => array($child_js)),
      '#title' => 'Child',
    );
    $element['child']['subchild'] = array(
      '#attached' => array('js' => array($subchild_js)),
      '#markup' => 'Subchild',
    );

    // Render the element and verify the presence of #attached JavaScript.
    drupal_render($element);
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, $parent_js), 'The element #attached JavaScript was included.');
    $this->assertTrue(strpos($scripts, $child_js), 'The child #attached JavaScript was included.');
    $this->assertTrue(strpos($scripts, $subchild_js), 'The subchild #attached JavaScript was included.');

    // Load the element from cache and verify the presence of the #attached
    // JavaScript.
    drupal_static_reset('drupal_add_js');
    $element = array('#cache' => array('keys' => array('simpletest', 'drupal_render', 'children_attached')));
    $this->assertTrue(strlen(drupal_render($element)) > 0, 'The element was retrieved from cache.');
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, $parent_js), 'The element #attached JavaScript was included when loading from cache.');
    $this->assertTrue(strpos($scripts, $child_js), 'The child #attached JavaScript was included when loading from cache.');
    $this->assertTrue(strpos($scripts, $subchild_js), 'The subchild #attached JavaScript was included when loading from cache.');

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
      '#foo' => $this->randomName(),
      '#bar' => $this->randomName(),
    );
    // Tests that passing arguments to the theme function works.
    $this->assertEqual(drupal_render($element), $element['#foo'] . $element['#bar'], 'Passing arguments to theme functions works');
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
        'tags' => array('render_cache_tag' => TRUE),
      ),
      '#markup' => '',
      'child' => array(
        '#cache' => array(
          'cid' => 'render_cache_test_child',
          'tags' => array('render_cache_tag_child' => array(1, 2))
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
      'render_cache_tag' => TRUE,
      'render_cache_tag_child' => array(1 => 1, 2 => 2),
    );
    $actual_tags = drupal_render_collect_cache_tags($test_element);
    $this->assertEqual($expected_tags, $actual_tags, 'Cache tags were collected from the element and its subchild.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }

  /**
   * Tests post-render cache callbacks functionality.
   */
  function testDrupalRenderPostRenderCache() {
    $context = array('foo' => $this->randomString());
    $test_element = array();
    $test_element['#markup'] = '';
    $test_element['#attached']['js'][] = array('type' => 'setting', 'data' => array('foo' => 'bar'));
    $test_element['#post_render_cache']['common_test_post_render_cache'] = array(
      $context
    );

    // #cache disabled.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $element['#markup'] = '<p>#cache disabled</p>';
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['foo'], 'bar', 'Original JavaScript setting is added to the page.');
    $this->assertIdentical($settings['common_test'], $context, '#attached is modified; JavaScript setting is added to page.');

    // The cache system is turned off for POST requests.
    $request_method = \Drupal::request()->getMethod();
    \Drupal::request()->setMethod('GET');

    // GET request: #cache enabled, cache miss.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $element['#cache'] = array('cid' => 'post_render_cache_test_GET');
    $element['#markup'] = '<p>#cache enabled, GET</p>';
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['foo'], 'bar', 'Original JavaScript setting is added to the page.');
    $this->assertIdentical($settings['common_test'], $context, '#attached is modified; JavaScript setting is added to page.');

    // GET request: validate cached data.
    $element = array('#cache' => array('cid' => 'post_render_cache_test_GET'));
    $cached_element = cache()->get(drupal_render_cid_create($element))->data;
    $expected_element = array(
      '#markup' => '<p>#cache enabled, GET</p>',
      '#attached' => $test_element['#attached'],
      '#post_render_cache' => $test_element['#post_render_cache'],
    );
    $this->assertIdentical($cached_element, $expected_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    drupal_static_reset('drupal_add_js');
    $element['#cache'] = array('cid' => 'post_render_cache_test_GET');
    $element['#markup'] = '<p>#cache enabled, GET</p>';
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['foo'], 'bar', 'Original JavaScript setting is added to the page.');
    $this->assertIdentical($settings['common_test'], $context, '#attached is modified; JavaScript setting is added to page.');

    // Verify behavior when handling a non-GET request, e.g. a POST request:
    // also in that case, #post_render_cache callbacks must be called.
    \Drupal::request()->setMethod('POST');

    // POST request: #cache enabled, cache miss.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $element['#cache'] = array('cid' => 'post_render_cache_test_POST');
    $element['#markup'] = '<p>#cache enabled, POST</p>';
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['foo'], 'bar', 'Original JavaScript setting is added to the page.');
    $this->assertIdentical($settings['common_test'], $context, '#attached is modified; JavaScript setting is added to page.');

    // POST request: Ensure no data was cached.
    $element = array('#cache' => array('cid' => 'post_render_cache_test_POST'));
    $cached_element = cache()->get(drupal_render_cid_create($element));
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
    drupal_static_reset('drupal_add_js');
    $context_1 = array('foo' => $this->randomString());
    $context_2 = array('bar' => $this->randomString());
    $context_3 = array('baz' => $this->randomString());
    $test_element = array(
      '#type' => 'details',
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
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $expected_settings = $context_1 + $context_2 + $context_3;
    $this->assertIdentical($settings['foo'], 'bar', 'Original JavaScript setting is added to the page.');
    $this->assertIdentical($settings['common_test'], $expected_settings, '#attached is modified; JavaScript settings for each #post_render_cache callback are added to page.');

    // GET request: validate cached data.
    $element = array('#cache' => $element['#cache']);
    $cached_element = cache()->get(drupal_render_cid_create($element))->data;
    $expected_element = array(
      '#markup' => '<details class="form-wrapper" open="open"><summary role="button" aria-expanded>Parent</summary><div class="details-wrapper"><details class="form-wrapper" open="open"><summary role="button" aria-expanded>Child</summary><div class="details-wrapper">Subchild</div></details>
</div></details>
',
      '#attached' => array(
        'js' => array(
          array('type' => 'setting', 'data' => array('foo' => 'bar'))
        ),
        'library' => array(
          array('system', 'drupal.collapse'),
          array('system', 'drupal.collapse'),
        ),
      ),
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array(
          $context_1,
          $context_2,
          $context_3,
        )
      ),
    );
    $this->assertIdentical($cached_element, $expected_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['foo'], 'bar', 'Original JavaScript setting is added to the page.');
    $this->assertIdentical($settings['common_test'], $expected_settings, '#attached is modified; JavaScript settings for each #post_render_cache callback are added to page.');

    // Test case 2.
    // Create an element with a child and subchild. Each element has the same
    // #post_render_cache callback, but with different contexts. Both the
    // parent and the child elements have #cache set. The cached parent element
    // must contain the pristine child element, i.e. unaffected by its
    // #post_render_cache callbacks. I.e. the #post_render_cache callbacks may
    // not yet have run, or otherwise the cached parent element would contain
    // personalized data, thereby breaking the render cache.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $element['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_parent');
    $element['child']['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_child');
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], '<p>overridden</p>', '#markup is overridden.');
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $expected_settings = $context_1 + $context_2 + $context_3;
    $this->assertIdentical($settings['foo'], 'bar', 'Original JavaScript setting is added to the page.');
    $this->assertIdentical($settings['common_test'], $expected_settings, '#attached is modified; JavaScript settings for each #post_render_cache callback are added to page.');

    // GET request: validate cached data for both the parent and child.
    $element = $test_element;
    $element['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_parent');
    $element['child']['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_child');
    $cached_parent_element = cache()->get(drupal_render_cid_create($element))->data;
    $cached_child_element = cache()->get(drupal_render_cid_create($element['child']))->data;
    $expected_parent_element = array(
      '#markup' => '<details class="form-wrapper" open="open"><summary role="button" aria-expanded>Parent</summary><div class="details-wrapper"><details class="form-wrapper" open="open"><summary role="button" aria-expanded>Child</summary><div class="details-wrapper">Subchild</div></details>
</div></details>
',
      '#attached' => array(
        'js' => array(
          array('type' => 'setting', 'data' => array('foo' => 'bar'))
        ),
        'library' => array(
          array('system', 'drupal.collapse'),
          array('system', 'drupal.collapse'),
        ),
      ),
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array(
          $context_1,
          $context_2,
          $context_3,
        )
      ),
    );
    $this->assertIdentical($cached_parent_element, $expected_parent_element, 'The correct data is cached for the parent: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');
    $expected_child_element = array(
      '#markup' => '<details class="form-wrapper" open="open"><summary role="button" aria-expanded>Child</summary><div class="details-wrapper">Subchild</div></details>
',
      '#attached' => array(
        'library' => array(
          array('system', 'drupal.collapse'),
        ),
      ),
      '#post_render_cache' => array(
        'common_test_post_render_cache' => array(
          $context_2,
          $context_3,
        )
      ),
    );
    $this->assertIdentical($cached_child_element, $expected_child_element, 'The correct data is cached for the child: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit, parent element.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $element['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_parent');
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['foo'], 'bar', 'Original JavaScript setting is added to the page.');
    $this->assertIdentical($settings['common_test'], $expected_settings, '#attached is modified; JavaScript settings for each #post_render_cache callback are added to page.');

    // GET request: #cache enabled, cache hit, child element.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $element['child']['#cache']['keys'] = array('simpletest', 'drupal_render', 'children_post_render_cache', 'nested_cache_child');
    $element = $element['child'];
    $output = drupal_render($element);
    $this->assertIdentical($output, '<p>overridden</p>', 'Output is overridden.');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertTrue(!isset($element['#context_test']), '#context_test is not set: impossible to modify $element itself, only possible to modify its #markup and #attached properties.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $expected_settings = $context_2 + $context_3;
    $this->assertTrue(!isset($settings['foo']), 'Parent JavaScript setting is not added to the page.');
    $this->assertIdentical($settings['common_test'], $expected_settings, '#attached is modified; JavaScript settings for each #post_render_cache callback are added to page.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }


  /**
   * Tests post-render cache-integrated 'render_cache_placeholder' element.
   */
  function testDrupalRenderRenderCachePlaceholder() {
    $context = array('bar' => $this->randomString());
    $test_element = array(
      '#type' => 'render_cache_placeholder',
      '#context' => $context,
      '#callback' => 'common_test_post_render_cache_placeholder',
      '#prefix' => '<foo>',
      '#suffix' => '</foo>'
    );
    $expected_output = '<foo><bar>' . $context['bar'] . '</bar></foo>';

    // #cache disabled.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['common_test'], $context, '#attached is modified; JavaScript setting is added to page.');

    // The cache system is turned off for POST requests.
    $request_method = \Drupal::request()->getMethod();
    \Drupal::request()->setMethod('GET');

    // GET request: #cache enabled, cache miss.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $element['#cache'] = array('cid' => 'render_cache_placeholder_test_GET');
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertTrue(isset($element['#printed']), 'No cache hit');
    $this->assertIdentical($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['common_test'], $context, '#attached is modified; JavaScript setting is added to page.');

    // GET request: validate cached data.
    $element = array('#cache' => array('cid' => 'render_cache_placeholder_test_GET'));
    $cached_element = cache()->get(drupal_render_cid_create($element))->data;
    // Parse unique token out of the markup.
    $dom = filter_dom_load($cached_element['#markup']);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//*[@token]');
    $token = $nodes->item(0)->getAttribute('token');
    $expected_element = array(
      '#markup' => '<foo><drupal:render-cache-placeholder callback="common_test_post_render_cache_placeholder" context="bar:' . $context['bar'] .';" token="'. $token . '" /></foo>',
      '#post_render_cache' => array(
        'common_test_post_render_cache_placeholder' => array(
          $token => $context,
        ),
      ),
    );
    $this->assertIdentical($cached_element, $expected_element, 'The correct data is cached: the stored #markup and #attached properties are not affected by #post_render_cache callbacks.');

    // GET request: #cache enabled, cache hit.
    drupal_static_reset('drupal_add_js');
    $element = $test_element;
    $element['#cache'] = array('cid' => 'render_cache_placeholder_test_GET');
    $output = drupal_render($element);
    $this->assertIdentical($output, $expected_output, 'Placeholder was replaced in output');
    $this->assertFalse(isset($element['#printed']), 'Cache hit');
    $this->assertIdentical($element['#markup'], $expected_output, 'Placeholder was replaced in #markup.');
    $settings = $this->parseDrupalSettings(drupal_get_js());
    $this->assertIdentical($settings['common_test'], $context, '#attached is modified; JavaScript setting is added to page.');

    // Restore the previous request method.
    \Drupal::request()->setMethod($request_method);
  }

  protected function parseDrupalSettings($html) {
    $startToken = 'drupalSettings = ';
    $endToken = '}';
    $start = strpos($html, $startToken) + strlen($startToken);
    $end = strrpos($html, $endToken);
    $json  = drupal_substr($html, $start, $end - $start + 1);
    $parsed_settings = drupal_json_decode($json);
    return $parsed_settings;
  }

}
