<?php

namespace Drupal\theme_test\Element;

use Drupal\Core\Render\Element\Container;

/**
 * Provides a render element for the theme_test_render_element_context element.
 *
 * @RenderElement("theme_test_render_element_context")
 */
class ThemeTestRenderElementContext extends Container {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return ['#theme_wrappers' => ['theme_test_render_element_context']] + parent::getInfo();
  }

}
