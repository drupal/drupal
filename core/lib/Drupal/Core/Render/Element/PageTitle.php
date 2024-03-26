<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElement as RenderElementBase;

/**
 * Provides a render element for the title of an HTML page.
 *
 * This represents the title of the HTML page's body.
 */
#[RenderElement('page_title')]
class PageTitle extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'page_title',
      // The page title: either a string for plain titles or a render array for
      // formatted titles.
      '#title' => NULL,
    ];
  }

}
