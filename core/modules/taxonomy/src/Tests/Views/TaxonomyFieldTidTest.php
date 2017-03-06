<?php

namespace Drupal\taxonomy\Tests\Views;

use Drupal\Core\Render\RenderContext;
use Drupal\views\Views;

/**
 * Tests the taxonomy term TID field handler.
 *
 * @group taxonomy
 */
class TaxonomyFieldTidTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_taxonomy_tid_field'];

  public function testViewsHandlerTidField() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_taxonomy_tid_field');
    $this->executeView($view);

    $actual = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $expected = \Drupal::l($this->term1->label(), $this->term1->urlInfo());

    $this->assertEqual($expected, $actual);
  }

}
