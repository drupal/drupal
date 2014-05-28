<?php

/**
 * @file
 * Contains Drupal\system\Tests\Common\RenderElementTypesTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Component\Utility\String;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the markup of core render element types passed to drupal_render().
 */
class RenderElementTypesTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  public static function getInfo() {
    return array(
      'name' => 'Render element types',
      'description' => 'Tests the markup of core render element types passed to drupal_render().',
      'group' => 'Common',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
  }

  /**
   * Asserts that an array of elements is rendered properly.
   *
   * @param array $elements
   *   The render element array to test.
   * @param string $expected_html
   *   The expected markup.
   * @param string $message
   *   Assertion message.
   */
  protected function assertElements(array $elements, $expected_html, $message) {
    $actual_html = drupal_render($elements);

    $out = '<table><tr>';
    $out .= '<td valign="top"><pre>' . String::checkPlain($expected_html) . '</pre></td>';
    $out .= '<td valign="top"><pre>' . String::checkPlain($actual_html) . '</pre></td>';
    $out .= '</tr></table>';
    $this->verbose($out);

    $this->assertIdentical($actual_html, $expected_html, String::checkPlain($message));
  }

  /**
   * Tests system #type 'container'.
   */
  function testContainer() {
    // Basic container with no attributes.
    $this->assertElements(array(
      '#type' => 'container',
      '#markup' => 'foo',
    ), "<div>foo</div>\n", "#type 'container' with no HTML attributes");

    // Container with a class.
    $this->assertElements(array(
      '#type' => 'container',
      '#markup' => 'foo',
      '#attributes' => array(
        'class' => 'bar',
      ),
    ), '<div class="bar">foo</div>' . "\n", "#type 'container' with a class HTML attribute");

    // Container with children.
    $this->assertElements(array(
      '#type' => 'container',
      'child' => array(
        '#markup' => 'foo',
      ),
    ), "<div>foo</div>\n", "#type 'container' with child elements");
  }

  /**
   * Tests system #type 'html_tag'.
   */
  function testHtmlTag() {
    // Test auto-closure meta tag generation.
    $this->assertElements(array(
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'description',
        'content' => 'Drupal test',
      ),
    ), '<meta name="description" content="Drupal test" />' . "\n", "#type 'html_tag' auto-closure meta tag generation");

    // Test title tag generation.
    $this->assertElements(array(
      '#type' => 'html_tag',
      '#tag' => 'title',
      '#value' => 'title test',
    ), "<title>title test</title>\n", "#type 'html_tag' title tag generation");
  }

}
