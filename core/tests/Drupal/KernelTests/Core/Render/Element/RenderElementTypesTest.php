<?php

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

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
  public static $modules = ['system', 'router_test'];

  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
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
  public function testContainer() {
    // Basic container with no attributes.
    $this->assertElements([
      '#type' => 'container',
      '#markup' => 'foo',
    ], "<div>foo</div>\n", "#type 'container' with no HTML attributes");

    // Container with a class.
    $this->assertElements([
      '#type' => 'container',
      '#markup' => 'foo',
      '#attributes' => [
        'class' => ['bar'],
      ],
    ], '<div class="bar">foo</div>' . "\n", "#type 'container' with a class HTML attribute");

    // Container with children.
    $this->assertElements([
      '#type' => 'container',
      'child' => [
        '#markup' => 'foo',
      ],
    ], "<div>foo</div>\n", "#type 'container' with child elements");
  }

  /**
   * Tests system #type 'html_tag'.
   */
  public function testHtmlTag() {
    // Test void element.
    $this->assertElements([
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#value' => 'ignored',
      '#attributes' => [
        'name' => 'description',
        'content' => 'Drupal test',
      ],
    ], '<meta name="description" content="Drupal test" />' . "\n", "#type 'html_tag', void element renders properly");

    // Test non-void element.
    $this->assertElements([
      '#type' => 'html_tag',
      '#tag' => 'section',
      '#value' => 'value',
      '#attributes' => [
        'class' => ['unicorns'],
      ],
    ], '<section class="unicorns">value</section>' . "\n", "#type 'html_tag', non-void element renders properly");

    // Test empty void element tag.
    $this->assertElements([
      '#type' => 'html_tag',
      '#tag' => 'link',
    ], "<link />\n", "#type 'html_tag' empty void element renders properly");

    // Test empty non-void element tag.
    $this->assertElements([
      '#type' => 'html_tag',
      '#tag' => 'section',
    ], "<section></section>\n", "#type 'html_tag' empty non-void element renders properly");
  }

  /**
   * Tests system #type 'more_link'.
   */
  public function testMoreLink() {
    $elements = [
      [
        'name' => "#type 'more_link' anchor tag generation without extra classes",
        'value' => [
          '#type' => 'more_link',
          '#url' => Url::fromUri('https://www.drupal.org'),
        ],
        'expected' => '//div[@class="more-link"]/a[@href="https://www.drupal.org" and text()="More"]',
      ],
      [
        'name' => "#type 'more_link' anchor tag generation with different link text",
        'value' => [
          '#type' => 'more_link',
          '#url' => Url::fromUri('https://www.drupal.org'),
          '#title' => 'More Titles',
        ],
        'expected' => '//div[@class="more-link"]/a[@href="https://www.drupal.org" and text()="More Titles"]',
      ],
      [
        'name' => "#type 'more_link' anchor tag generation with attributes on wrapper",
        'value' => [
          '#type' => 'more_link',
          '#url' => Url::fromUri('https://www.drupal.org'),
          '#theme_wrappers' => [
            'container' => [
              '#attributes' => [
                'title' => 'description',
                'class' => ['more-link', 'drupal', 'test'],
              ],
            ],
          ],
        ],
        'expected' => '//div[@title="description" and contains(@class, "more-link") and contains(@class, "drupal") and contains(@class, "test")]/a[@href="https://www.drupal.org" and text()="More"]',
      ],
      [
        'name' => "#type 'more_link' anchor tag with a relative path",
        'value' => [
          '#type' => 'more_link',
          '#url' => Url::fromRoute('router_test.1'),
        ],
        'expected' => '//div[@class="more-link"]/a[@href="' . Url::fromRoute('router_test.1')->toString() . '" and text()="More"]',
      ],
      [
        'name' => "#type 'more_link' anchor tag with a route",
        'value' => [
          '#type' => 'more_link',
          '#url' => Url::fromRoute('router_test.1'),
        ],
        'expected' => '//div[@class="more-link"]/a[@href="' . \Drupal::urlGenerator()->generate('router_test.1') . '" and text()="More"]',
      ],
      [
        'name' => "#type 'more_link' anchor tag with an absolute path",
        'value' => [
          '#type' => 'more_link',
          '#url' => Url::fromRoute('system.admin_content'),
          '#options' => ['absolute' => TRUE],
        ],
        'expected' => '//div[@class="more-link"]/a[@href="' . Url::fromRoute('system.admin_content')->setAbsolute()->toString() . '" and text()="More"]',
      ],
      [
        'name' => "#type 'more_link' anchor tag to the front page",
        'value' => [
          '#type' => 'more_link',
          '#url' => Url::fromRoute('<front>'),
        ],
        'expected' => '//div[@class="more-link"]/a[@href="' . Url::fromRoute('<front>')->toString() . '" and text()="More"]',
      ],
    ];

    foreach ($elements as $element) {
      $xml = new \SimpleXMLElement(\Drupal::service('renderer')->renderRoot($element['value']));
      $result = $xml->xpath($element['expected']);
      $this->assertNotEmpty($result, '"' . $element['name'] . '" input rendered correctly by drupal_render().');
    }
  }

  /**
   * Tests system #type 'system_compact_link'.
   */
  public function testSystemCompactLink() {
    $elements = [
      [
        'name' => "#type 'system_compact_link' when admin compact mode is off",
        'value' => [
          '#type' => 'system_compact_link',
        ],
        'expected' => '//div[@class="compact-link"]/a[contains(@href, "admin/compact/on?") and text()="Hide descriptions"]',
      ],
      [
        'name' => "#type 'system_compact_link' when adding extra attributes",
        'value' => [
          '#type' => 'system_compact_link',
          '#attributes' => [
            'class' => ['kittens-rule'],
          ],
        ],
        'expected' => '//div[@class="compact-link"]/a[contains(@href, "admin/compact/on?") and @class="kittens-rule" and text()="Hide descriptions"]',
      ],
    ];

    foreach ($elements as $element) {
      $xml = new \SimpleXMLElement(\Drupal::service('renderer')->renderRoot($element['value']));
      $result = $xml->xpath($element['expected']);
      $this->assertNotEmpty($result, '"' . $element['name'] . '" is rendered correctly by drupal_render().');
    }

    // Set admin compact mode on for additional tests.
    \Drupal::request()->cookies->set('Drupal_visitor_admin_compact_mode', TRUE);

    $element = [
      'name' => "#type 'system_compact_link' when admin compact mode is on",
      'value' => [
        '#type' => 'system_compact_link',
      ],
      'expected' => '//div[@class="compact-link"]/a[contains(@href, "admin/compact?") and text()="Show descriptions"]',
    ];

    $xml = new \SimpleXMLElement(\Drupal::service('renderer')->renderRoot($element['value']));
    $result = $xml->xpath($element['expected']);
    $this->assertNotEmpty($result, '"' . $element['name'] . '" is rendered correctly by drupal_render().');
  }

}
