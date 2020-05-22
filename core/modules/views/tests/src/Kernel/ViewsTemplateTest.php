<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\views\Views;

/**
 * Tests the template retrieval of views.
 *
 * @group views
 *
 * @see \Drupal\views_test_data\Plugin\views\style\StyleTemplateTest
 */
class ViewsTemplateTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view_display_template'];

  /**
   * Tests render functionality.
   */
  public function testTemplate() {
    // Make sure that the rendering just calls the preprocess function once.
    $output = Views::getView('test_view_display_template')->preview();
    $renderer = $this->container->get('renderer');

    // Check that the renderd output uses the correct template file.
    $this->assertStringContainsString('This module defines its own display template.', (string) $renderer->renderRoot($output));
  }

}
