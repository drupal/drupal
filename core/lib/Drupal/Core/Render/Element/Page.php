<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Page.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for the content of an HTML page.
 *
 * This represents the "main part" of the HTML page's body; see html.html.twig.
 *
 * @RenderElement("page")
 */
class Page extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#theme' => 'page',
      '#title' => '',
    );
  }

}
