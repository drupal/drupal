<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;

/**
 * Provides a render element for the content of an HTML page.
 *
 * This represents the "main part" of the HTML page's body; see html.html.twig.
  */
#[RenderElement('page')]
class Page extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'page',
      '#title' => '',
    ];
  }

}
