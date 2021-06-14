<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests fields style functionality.
 *
 * @group views
 *
 * @see \Drupal\views\Plugin\views\row\Fields.
 */
class StyleFieldsTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view'];

  /**
   * Tests inline fields and separator.
   */
  public function testInlineFields() {
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Test using an HTML separator.
    $row = $view->display_handler->getOption('row');
    $row['options'] = [
      'inline' => [
        'age' => 'age',
        'id' => 'id',
        'name' => 'name',
      ],
      'separator' => '<br />',
    ];
    $view->display_handler->setOption('row', $row);
    $view->initDisplay();
    $view->initStyle();
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    $this->assertStringContainsString('<div class="views-row"><span class="views-field views-field-age"><span class="field-content">25</span></span><br /><span class="views-field views-field-id"><span class="field-content">1</span></span><br /><span class="views-field views-field-name"><span class="field-content">John</span></span></div>', (string) $output);
    $view->destroy();

    // Check that unsafe separators are stripped.
    $view->setDisplay();
    $row = $view->display_handler->getOption('row');
    $row['options'] = [
      'inline' => [
        'age' => 'age',
        'id' => 'id',
        'name' => 'name',
      ],
      'separator' => '<script>alert("escape me!")</script>',
    ];
    $view->display_handler->setOption('row', $row);
    $view->initDisplay();
    $view->initStyle();
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    $this->assertStringNotContainsString('<script>', (string) $output);
    $this->assertStringContainsString('alert("escape me!")', (string) $output);
  }

}
