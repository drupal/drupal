<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElement as RenderElementBase;

/**
 * Provides a render element for an entire HTML page: <html> plus its children.
 */
#[RenderElement('html')]
class Html extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'html',
    ];
  }

}
