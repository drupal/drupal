<?php

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for an entire HTML page: <html> plus its children.
 *
 * @RenderElement("html")
 */
class Html extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'html',
    ];
  }

}
