<?php

namespace Drupal\system\Tests\Common;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the markup of core render element types passed to drupal_render().
 *
 * @group Common
 */
class RenderElementTypesTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'router_test');

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
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
    $actual_html = (string) \Drupal::service('renderer')->renderRoot($elements);

    $out = '<table><tr>';
    $out .= '<td valign="top"><pre>' . Html::escape($expected_html) . '</pre></td>';
    $out .= '<td valign="top"><pre>' . Html::escape($actual_html) . '</pre></td>';
    $out .= '</tr></table>';
    $this->verbose($out);

    $this->assertIdentical($actual_html, $expected_html, Html::escape($message));
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
    // Test void element.
    $this->assertElements(array(
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#value' => 'ignored',
      '#attributes' => array(
        'name' => 'description',
        'content' => 'Drupal test',
      ),
    ), '<meta name="description" content="Drupal test" />' . "\n", "#type 'html_tag', void element renders properly");

    // Test non-void element.
    $this->assertElements(array(
      '#type' => 'html_tag',
      '#tag' => 'section',
      '#value' => 'value',
      '#attributes' => array(
        'class' => array('unicorns'),
      ),
    ), '<section class="unicorns">value</section>' . "\n", "#type 'html_tag', non-void element renders properly");

    // Test empty void element tag.
    $this->assertElements(array(
      '#type' => 'html_tag',
      '#tag' => 'link',
    ), "<link />\n", "#type 'html_tag' empty void element renders properly");

    // Test empty non-void element tag.
    $this->assertElements(array(
      '#type' => 'html_tag',
      '#tag' => 'section',
    ), "<section></section>\n", "#type 'html_tag' empty non-void element renders properly");
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
          '#url' => Url::fromUri('https://www.drupal.org'),
        ),
        'expected' => '//div[@class="more-link"]/a[@href="https://www.drupal.org" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag generation with different link text",
        'value' => array(
          '#type' => 'more_link',
          '#url' => Url::fromUri('https://www.drupal.org'),
          '#title' => 'More Titles',
        ),
        'expected' => '//div[@class="more-link"]/a[@href="https://www.drupal.org" and text()="More Titles"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag generation with attributes on wrapper",
        'value' => array(
          '#type' => 'more_link',
          '#url' => Url::fromUri('https://www.drupal.org'),
          '#theme_wrappers' => array(
            'container' => array(
              '#attributes' => array(
                'title' => 'description',
                'class' => array('more-link', 'drupal', 'test'),
              ),
            ),
          ),
        ),
        'expected' => '//div[@title="description" and contains(@class, "more-link") and contains(@class, "drupal") and contains(@class, "test")]/a[@href="https://www.drupal.org" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag with a relative path",
        'value' => array(
          '#type' => 'more_link',
          '#url' => Url::fromRoute('router_test.1'),
        ),
        'expected' => '//div[@class="more-link"]/a[@href="' . Url::fromRoute('router_test.1')->toString() . '" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag with a route",
        'value' => array(
          '#type' => 'more_link',
          '#url' => Url::fromRoute('router_test.1'),
        ),
        'expected' => '//div[@class="more-link"]/a[@href="' . \Drupal::urlGenerator()->generate('router_test.1') . '" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag with an absolute path",
        'value' => array(
          '#type' => 'more_link',
          '#url' => Url::fromRoute('system.admin_content'),
          '#options' => array('absolute' => TRUE),
        ),
        'expected' => '//div[@class="more-link"]/a[@href="' . Url::fromRoute('system.admin_content')->setAbsolute()->toString() . '" and text()="More"]',
      ),
      array(
        'name' => "#type 'more_link' anchor tag to the front page",
        'value' => array(
          '#type' => 'more_link',
          '#url' => Url::fromRoute('<front>'),
        ),
        'expected' => '//div[@class="more-link"]/a[@href="' . Url::fromRoute('<front>')->toString() . '" and text()="More"]',
      ),
    );

    foreach ($elements as $element) {
      $xml = new \SimpleXMLElement(\Drupal::service('renderer')->renderRoot($element['value']));
      $result = $xml->xpath($element['expected']);
      $this->assertTrue($result, '"' . $element['name'] . '" input rendered correctly by drupal_render().');
    }
  }

  /**
   * Tests system #type 'system_compact_link'.
   */
  function testSystemCompactLink() {
    $elements = array(
      array(
        'name' => "#type 'system_compact_link' when admin compact mode is off",
        'value' => array(
          '#type' => 'system_compact_link',
        ),
        'expected' => '//div[@class="compact-link"]/a[contains(@href, "admin/compact/on?") and text()="Hide descriptions"]',
      ),
      array(
        'name' => "#type 'system_compact_link' when adding extra attributes",
        'value' => array(
          '#type' => 'system_compact_link',
          '#attributes' => array(
            'class' => array('kittens-rule'),
          ),
        ),
        'expected' => '//div[@class="compact-link"]/a[contains(@href, "admin/compact/on?") and @class="kittens-rule" and text()="Hide descriptions"]',
      ),
    );

    foreach ($elements as $element) {
      $xml = new \SimpleXMLElement(\Drupal::service('renderer')->renderRoot($element['value']));
      $result = $xml->xpath($element['expected']);
      $this->assertTrue($result, '"' . $element['name'] . '" is rendered correctly by drupal_render().');
    }

    // Set admin compact mode on for additional tests.
    \Drupal::request()->cookies->set('Drupal_visitor_admin_compact_mode', TRUE);

    $element = array(
      'name' => "#type 'system_compact_link' when admin compact mode is on",
      'value' => array(
        '#type' => 'system_compact_link',
      ),
      'expected' => '//div[@class="compact-link"]/a[contains(@href, "admin/compact?") and text()="Show descriptions"]',
    );

    $xml = new \SimpleXMLElement(\Drupal::service('renderer')->renderRoot($element['value']));
    $result = $xml->xpath($element['expected']);
    $this->assertTrue($result, '"' . $element['name'] . '" is rendered correctly by drupal_render().');
  }

}
