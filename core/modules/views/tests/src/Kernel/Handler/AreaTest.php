<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Component\Utility\Xss;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the plugin base of the area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\AreaPluginBase
 * @see \Drupal\views_test\Plugin\views\area\TestExample
 */
class AreaTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_example_area', 'test_example_area_access'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views']['test_example'] = [
      'title' => 'Test Example area',
      'help' => 'A area handler which just exists for tests.',
      'area' => [
        'id' => 'test_example',
      ],
    ];

    return $data;
  }

  /**
   * Tests the rendering of an area.
   */
  public function testRenderArea() {
    $view = Views::getView('test_example_area');
    $view->initHandlers();

    // Insert a random string with XSS injection in the test area plugin.
    // Ensure that the string is rendered for the header, footer, and empty
    // text with the markup properly escaped.
    $header_string = '<script type="text/javascript">alert("boo");</script><p>' . $this->randomMachineName() . '</p>';
    $footer_string = '<script type="text/javascript">alert("boo");</script><p>' . $this->randomMachineName() . '</p>';
    $empty_string = '<script type="text/javascript">alert("boo");</script><p>' . $this->randomMachineName() . '</p>';

    $view->header['test_example']->options['string'] = $header_string;
    $view->header['test_example']->options['empty'] = TRUE;

    $view->footer['test_example']->options['string'] = $footer_string;
    $view->footer['test_example']->options['empty'] = TRUE;

    $view->empty['test_example']->options['string'] = $empty_string;

    // Check whether the strings exist in the output and are sanitized.
    $output = $view->preview();
    $output = (string) $this->container->get('renderer')->renderRoot($output);
    $this->assertStringContainsString(Xss::filterAdmin($header_string), $output, 'Views header exists in the output and is sanitized');
    $this->assertStringContainsString(Xss::filterAdmin($footer_string), $output, 'Views footer exists in the output and is sanitized');
    $this->assertStringContainsString(Xss::filterAdmin($empty_string), $output, 'Views empty exists in the output and is sanitized');
    $this->assertStringNotContainsString('<script', $output, 'Script tags were escaped');
  }

  /**
   * Tests the access for an area.
   */
  public function testAreaAccess() {
    // Test with access denied for the area handler.
    $view = Views::getView('test_example_area_access');
    $view->initDisplay();
    $view->initHandlers();
    $handlers = $view->display_handler->getHandlers('empty');
    $this->assertCount(0, $handlers);

    $output = $view->preview();
    $output = (string) \Drupal::service('renderer')->renderRoot($output);
    // The area output should not be present since access was denied.
    $this->assertStringNotContainsString('a custom string', $output);
    $view->destroy();

    // Test with access granted for the area handler.
    $view = Views::getView('test_example_area_access');
    $view->initDisplay();
    $view->display_handler->overrideOption('empty', [
      'test_example' => [
        'field' => 'test_example',
        'id' => 'test_example',
        'table' => 'views',
        'plugin_id' => 'test_example',
        'string' => 'a custom string',
        'custom_access' => TRUE,
      ],
    ]);
    $view->initHandlers();
    $handlers = $view->display_handler->getHandlers('empty');

    $output = $view->preview();
    $output = (string) \Drupal::service('renderer')->renderRoot($output);
    $this->assertStringContainsString('a custom string', $output);
    $this->assertCount(1, $handlers);
  }

}
