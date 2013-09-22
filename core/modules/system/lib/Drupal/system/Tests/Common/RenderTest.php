<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\RenderTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests drupal_render().
 */
class RenderTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('common_test');

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
    $request_method = $_SERVER['REQUEST_METHOD'];
    $_SERVER['REQUEST_METHOD'] = 'GET';

    // Create an element with a child and subchild.  Each element loads a
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
    $this->assertTrue(drupal_render_cache_get($element), 'The element was retrieved from cache.');
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, $parent_js), 'The element #attached JavaScript was included when loading from cache.');
    $this->assertTrue(strpos($scripts, $child_js), 'The child #attached JavaScript was included when loading from cache.');
    $this->assertTrue(strpos($scripts, $subchild_js), 'The subchild #attached JavaScript was included when loading from cache.');

    $_SERVER['REQUEST_METHOD'] = $request_method;
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
   * Tests rendering form elements without passing through form_builder().
   */
  function testDrupalRenderFormElements() {
    // Define a series of form elements.
    $element = array(
      '#type' => 'button',
      '#value' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'submit'));

    $element = array(
      '#type' => 'textfield',
      '#title' => $this->randomName(),
      '#value' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'text'));

    $element = array(
      '#type' => 'password',
      '#title' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'password'));

    $element = array(
      '#type' => 'textarea',
      '#title' => $this->randomName(),
      '#value' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//textarea');

    $element = array(
      '#type' => 'radio',
      '#title' => $this->randomName(),
      '#value' => FALSE,
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'radio'));

    $element = array(
      '#type' => 'checkbox',
      '#title' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'checkbox'));

    $element = array(
      '#type' => 'select',
      '#title' => $this->randomName(),
      '#options' => array(
        0 => $this->randomName(),
        1 => $this->randomName(),
      ),
    );
    $this->assertRenderedElement($element, '//select');

    $element = array(
      '#type' => 'file',
      '#title' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'file'));

    $element = array(
      '#type' => 'item',
      '#title' => $this->randomName(),
      '#markup' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//div[contains(@class, :class) and contains(., :markup)]/label[contains(., :label)]', array(
      ':class' => 'form-type-item',
      ':markup' => $element['#markup'],
      ':label' => $element['#title'],
    ));

    $element = array(
      '#type' => 'hidden',
      '#title' => $this->randomName(),
      '#value' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'hidden'));

    $element = array(
      '#type' => 'link',
      '#title' => $this->randomName(),
      '#href' => $this->randomName(),
      '#options' => array(
        'absolute' => TRUE,
      ),
    );
    $this->assertRenderedElement($element, '//a[@href=:href and contains(., :title)]', array(
      ':href' => url($element['#href'], array('absolute' => TRUE)),
      ':title' => $element['#title'],
    ));

    $element = array(
      '#type' => 'details',
      '#title' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//details/summary[contains(., :title)]', array(
      ':title' => $element['#title'],
    ));

    $element = array(
      '#type' => 'details',
      '#title' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//details');

    $element['item'] = array(
      '#type' => 'item',
      '#title' => $this->randomName(),
      '#markup' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//details/div/div[contains(@class, :class) and contains(., :markup)]', array(
      ':class' => 'form-type-item',
      ':markup' => $element['item']['#markup'],
    ));
  }

  /**
   * Tests rendering elements with invalid keys.
   */
  function testDrupalRenderInvalidKeys() {
    $error = array(
      '%type' => 'User error',
      '!message' => '"child" is an invalid render array key',
      '%function' => 'element_children()',
    );
    $message = t('%type: !message in %function (line ', $error);

    \Drupal::config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_ALL)->save();
    $this->drupalGet('common-test/drupal-render-invalid-keys');
    $this->assertResponse(200, 'Received expected HTTP status code.');
    $this->assertRaw($message, format_string('Found error message: !message.', array('!message' => $message)));
  }

  /**
   * Tests that elements are rendered properly.
   */
  protected function assertRenderedElement(array $element, $xpath, array $xpath_args = array()) {
    $original_element = $element;
    $this->drupalSetContent(drupal_render($element));
    $this->verbose('<pre>' .  check_plain(var_export($original_element, TRUE)) . '</pre>'
      . '<pre>' .  check_plain(var_export($element, TRUE)) . '</pre>'
      . '<hr />' . $this->drupalGetContent()
    );

    // @see Drupal\simpletest\WebTestBase::xpath()
    $xpath = $this->buildXPathQuery($xpath, $xpath_args);
    $element += array('#value' => NULL);
    $this->assertFieldByXPath($xpath, $element['#value'], format_string('#type @type was properly rendered.', array(
      '@type' => var_export($element['#type'], TRUE),
    )));
  }

  /**
   * Tests caching of an empty render item.
   */
  function testDrupalRenderCache() {
    // Force a request via GET.
    $request_method = $_SERVER['REQUEST_METHOD'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
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
    $_SERVER['REQUEST_METHOD'] = $request_method;
  }
}
