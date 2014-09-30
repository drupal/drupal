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
 *
 * @group Common
 */
class RenderElementTypesTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'router_test');

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
    $this->installSchema('system', array('router'));
    \Drupal::service('router.builder')->rebuild();
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
        'class' => array('bar'),
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

  /**
   * Tests system #type 'more_link'.
   */
  function testMoreLink() {
    $elements = array(
      array(
        'name' => "#type 'more_link' anchor tag generation without extra classes",
        'value' => array(
          '#type' => 'more_link',
          '#href' => 'http://drupal.org',
        ),
        'expected' => '//div[@class="more-link"]/a[@href="http://drupal.org" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag generation with different link text",
        'value' => array(
          '#type' => 'more_link',
          '#href' => 'http://drupal.org',
          '#title' => 'More Titles',
        ),
        'expected' => '//div[@class="more-link"]/a[@href="http://drupal.org" and text()="More Titles"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag generation with attributes on wrapper",
        'value' => array(
          '#type' => 'more_link',
          '#href' => 'http://drupal.org',
          '#theme_wrappers' => array(
            'container' => array(
              '#attributes' => array(
                'title' => 'description',
                'class' => array('more-link', 'drupal', 'test'),
              ),
            ),
          ),
        ),
        'expected' => '//div[@title="description" and contains(@class, "more-link") and contains(@class, "drupal") and contains(@class, "test")]/a[@href="http://drupal.org" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag with a relative path",
        'value' => array(
          '#type' => 'more_link',
          '#href' => 'a/link',
        ),
        'expected' => '//div[@class="more-link"]/a[@href="' . _url('a/link') . '" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag with a route",
        'value' => array(
          '#type' => 'more_link',
          '#route_name' => 'router_test.1',
          '#route_parameters' => array(),
        ),
        'expected' => '//div[@class="more-link"]/a[@href="' . \Drupal::urlGenerator()->generate('router_test.1') . '" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag with an absolute path",
        'value' => array(
          '#type' => 'more_link',
          '#href' => 'admin/content',
          '#options' => array('absolute' => TRUE),
        ),
        'expected' => '//div[@class="more-link"]/a[@href="' . _url('admin/content', array('absolute' => TRUE)) . '" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag to the front page",
        'value' => array(
          '#type' => 'more_link',
          '#href' => '<front>',
        ),
        'expected' => '//div[@class="more-link"]/a[@href="' . _url('<front>') . '" and text()="More"]',
      ),
    );

    foreach($elements as $element) {
      $xml = new \SimpleXMLElement(drupal_render($element['value']));
      $result = $xml->xpath($element['expected']);
      $this->assertTrue($result, '"' . $element['name'] . '" input rendered correctly by drupal_render().');
    }
  }

}
