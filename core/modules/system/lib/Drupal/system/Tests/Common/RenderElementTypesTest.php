<?php

/**
 * @file
 * Contains Drupal\system\Tests\Common\RenderElementTypesTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the markup of core render element types passed to drupal_render().
 */
class RenderElementTypesTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Render element types',
      'description' => 'Tests the markup of core render element types passed to drupal_render().',
      'group' => 'Common',
    );
  }

  /**
   * Asserts that an array of elements is rendered properly.
   *
   * @param array $elements
   *   An array of associative arrays describing render elements and their
   *   expected markup. Each item in $elements must contain the following:
   *   - 'name': This human readable description will be displayed on the test
   *     results page.
   *   - 'value': This is the render element to test.
   *   - 'expected': This is the expected markup for the element in 'value'.
   */
  function assertElements($elements) {
    foreach($elements as $element) {
      $this->assertIdentical(drupal_render($element['value']), $element['expected'], '"' . $element['name'] . '" input rendered correctly by drupal_render().');
    }
  }

  /**
   * Tests system #type 'container'.
   */
  function testContainer() {
    $elements = array(
      // Basic container with no attributes.
      array(
        'name' => "#type 'container' with no HTML attributes",
        'value' => array(
          '#type' => 'container',
          '#markup' => 'foo',
        ),
        'expected' => '<div>foo</div>',
      ),
      // Container with a class.
      array(
        'name' => "#type 'container' with a class HTML attribute",
        'value' => array(
          '#type' => 'container',
          '#markup' => 'foo',
          '#attributes' => array(
            'class' => 'bar',
          ),
        ),
        'expected' => '<div class="bar">foo</div>',
      ),
      // Container with children.
      array(
        'name' => "#type 'container' with child elements",
        'value' => array(
          '#type' => 'container',
          'child' => array(
            '#markup' => 'foo',
          ),
        ),
        'expected' => '<div>foo</div>',
      ),
    );

    $this->assertElements($elements);
  }

  /**
   * Tests system #type 'html_tag'.
   */
  function testHtmlTag() {
    $elements = array(
      // Test auto-closure meta tag generation.
      array(
        'name' => "#type 'html_tag' auto-closure meta tag generation",
        'value' => array(
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => array(
            'name' => 'description',
            'content' => 'Drupal test',
          ),
        ),
        'expected' => '<meta name="description" content="Drupal test" />' . "\n",
      ),
      // Test title tag generation.
      array(
        'name' => "#type 'html_tag' title tag generation",
        'value' => array(
          '#type' => 'html_tag',
          '#tag' => 'title',
          '#value' => 'title test',
        ),
        'expected' => '<title>title test</title>' . "\n",
      ),
    );

    $this->assertElements($elements);
  }

}
