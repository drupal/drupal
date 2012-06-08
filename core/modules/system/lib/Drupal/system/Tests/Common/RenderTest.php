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
  public static function getInfo() {
    return array(
      'name' => 'drupal_render()',
      'description' => 'Performs functional tests on drupal_render().',
      'group' => 'Common',
    );
  }

  function setUp() {
    parent::setUp('common_test');
  }

  /**
   * Test sorting by weight.
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
    $this->assertTrue(strpos($output, $second) > strpos($output, $first), t('Elements were sorted correctly by weight.'));

    // Confirm that the $elements array has '#sorted' set to TRUE.
    $this->assertTrue($elements['#sorted'], t("'#sorted' => TRUE was added to the array"));

    // Pass $elements through element_children() and ensure it remains
    // sorted in the correct order. drupal_render() will return an empty string
    // if used on the same array in the same request.
    $children = element_children($elements);
    $this->assertTrue(array_shift($children) == 'first', t('Child found in the correct order.'));
    $this->assertTrue(array_shift($children) == 'second', t('Child found in the correct order.'));


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
    $this->assertTrue(strpos($output, $second) < strpos($output, $first), t('Elements were not sorted.'));
  }

  /**
   * Test #attached functionality in children elements.
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
      '#type' => 'fieldset',
      '#cache' => array(
        'keys' => array('simpletest', 'drupal_render', 'children_attached'),
      ),
      '#attached' => array('js' => array($parent_js)),
      '#title' => 'Parent',
    );
    $element['child'] = array(
      '#type' => 'fieldset',
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
    $this->assertTrue(strpos($scripts, $parent_js), t('The element #attached JavaScript was included.'));
    $this->assertTrue(strpos($scripts, $child_js), t('The child #attached JavaScript was included.'));
    $this->assertTrue(strpos($scripts, $subchild_js), t('The subchild #attached JavaScript was included.'));

    // Load the element from cache and verify the presence of the #attached
    // JavaScript.
    drupal_static_reset('drupal_add_js');
    $this->assertTrue(drupal_render_cache_get($element), t('The element was retrieved from cache.'));
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, $parent_js), t('The element #attached JavaScript was included when loading from cache.'));
    $this->assertTrue(strpos($scripts, $child_js), t('The child #attached JavaScript was included when loading from cache.'));
    $this->assertTrue(strpos($scripts, $subchild_js), t('The subchild #attached JavaScript was included when loading from cache.'));

    $_SERVER['REQUEST_METHOD'] = $request_method;
  }

  /**
   * Test passing arguments to the theme function.
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
    // Test that passing arguments to the theme function works.
    $this->assertEqual(drupal_render($element), $element['#foo'] . $element['#bar'], 'Passing arguments to theme functions works');
  }

  /**
   * Test rendering form elements without passing through form_builder().
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
      '#type' => 'fieldset',
      '#title' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//fieldset/legend[contains(., :title)]', array(
      ':title' => $element['#title'],
    ));

    $element = array(
      '#type' => 'fieldset',
      '#title' => $this->randomName(),
      '#collapsible' => TRUE,
    );
    $this->assertRenderedElement($element, '//fieldset[contains(@class, :class)]', array(
      ':class' => 'collapsible',
    ));

    $element['item'] = array(
      '#type' => 'item',
      '#title' => $this->randomName(),
      '#markup' => $this->randomName(),
    );
    $this->assertRenderedElement($element, '//fieldset/div/div[contains(@class, :class) and contains(., :markup)]', array(
      ':class' => 'form-type-item',
      ':markup' => $element['item']['#markup'],
    ));
  }

  /**
   * Test rendering elements with invalid keys.
   */
  function testDrupalRenderInvalidKeys() {
    $error = array(
      '%type' => 'User error',
      '!message' => '"child" is an invalid render array key',
      '%function' => 'element_children()',
    );
    $message = t('%type: !message in %function (line ', $error);

    variable_set('error_level', ERROR_REPORTING_DISPLAY_ALL);
    $this->drupalGet('common-test/drupal-render-invalid-keys');
    $this->assertResponse(200, t('Received expected HTTP status code.'));
    $this->assertRaw($message, t('Found error message: !message.', array('!message' => $message)));
  }

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
    $this->assertFieldByXPath($xpath, $element['#value'], t('#type @type was properly rendered.', array(
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
      ),
      '#markup' => '',
    );

    // Render the element and confirm that it goes through the rendering
    // process (which will set $element['#printed']).
    $element = $test_element;
    drupal_render($element);
    $this->assertTrue(isset($element['#printed']), t('No cache hit'));

    // Render the element again and confirm that it is retrieved from the cache
    // instead (so $element['#printed'] will not be set).
    $element = $test_element;
    drupal_render($element);
    $this->assertFalse(isset($element['#printed']), t('Cache hit'));

    // Restore the previous request method.
    $_SERVER['REQUEST_METHOD'] = $request_method;
  }
}
