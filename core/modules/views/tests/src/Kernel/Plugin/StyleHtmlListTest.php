<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the HTML list style plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\style\HtmlList
 */
class StyleHtmlListTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_style_html_list'];

  /**
   * Make sure that the HTML list style markup is correct.
   */
  public function testDefaultRowClasses() {
    $view = Views::getView('test_style_html_list');
    $output = $view->preview();
    $output = (string) \Drupal::service('renderer')->renderRoot($output);

    // Check that an empty class attribute is not added if the wrapper class is
    // not set.
    $this->assertStringContainsString('<div>', $output, 'Empty class is not added to DIV when class is not set');

    // Check that an empty class attribute is not added if the list class is
    // not set.
    $this->assertStringContainsString('<ul>', $output, 'Empty class is not added to UL when class is not set');

    // Set wrapper class and list class in style options.
    $view->style_plugin->options['class'] = 'class';
    $view->style_plugin->options['wrapper_class'] = 'wrapper-class';

    $output = $view->preview();
    $output = (string) \Drupal::service('renderer')->renderRoot($output);

    // Check that class attribute is present if the wrapper class is set.
    $this->assertStringContainsString('<div class="wrapper-class">', $output, 'Class is added to DIV');

    // Check that class attribute is present if the list class is set.
    $this->assertStringContainsString('<ul class="class">', $output, 'Class is added to UL');
  }

}
