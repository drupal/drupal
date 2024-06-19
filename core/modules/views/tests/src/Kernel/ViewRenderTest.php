<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\views\Views;

/**
 * Tests general rendering of a view.
 *
 * @group views
 */
class ViewRenderTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view_render'];

  /**
   * Tests render functionality.
   */
  public function testRender(): void {
    $state = $this->container->get('state');
    $state->set('views_render.test', 0);

    // Make sure that the rendering just calls the preprocess function once.
    $view = Views::getView('test_view_render');
    $output = $view->preview();
    $this->container->get('renderer')->renderRoot($output);

    $this->assertEquals(1, $state->get('views_render.test'));
  }

}
